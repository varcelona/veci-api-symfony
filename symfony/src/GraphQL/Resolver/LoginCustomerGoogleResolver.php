<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use App\Entity\Customer;
use App\GraphQL\Resource\Auth;
use App\Repository\UserRepository;
use App\Security\OAuth\GoogleOAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginCustomerGoogleResolver implements MutationResolverInterface
{
    public function __construct(
        private GoogleOAuthService $googleService,
        private UserRepository $userRepo,
        private EntityManagerInterface $em,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenManagerInterface $refreshManager,
        private TranslatorInterface $translator,
    ) {}

    public function __invoke(?object $item, array $context): ?object
    {
        $idToken = $context['args']['input']['idToken'] ?? null;

        $auth = new Auth();
        $auth->success = false;
        $auth->message = $this->translator->trans('ui.auth.invalid_expired_token');

        if (!$idToken) {
            return $auth;
        }

        // 1) Validar token de Google
        try {
            $googleUser = $this->googleService->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            return $auth;
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        // 2) Buscar usuario
        $user = $this->userRepo->findOneBy(['oauthProvider' => 'google', 'oauthId' => $googleId]);

        // 3) Si no existe → Crearlo
        if (!$user) {
            $user = $this->userRepo->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setEnabled(true);
                $user->setRoles(['ROLE_CUSTOMER']);
            }

            $user->setOauthProvider('google');
            $user->setOauthId($googleId);

            if (!$user->getCustomer()) {
                $customer = new Customer();
                $customer->setUser($user);
                $this->em->persist($customer);
            }

            $this->em->persist($user);
            $this->em->flush();
        }

        // 4) Emitir JWT
        $token = $this->jwtManager->create($user);

        // 5) Refresh Token (uno por usuario)
        $refresh = $this->refreshManager->getLastFromUsername($user->getEmail());

        if (!$refresh) {
            $refresh = $this->refreshManager->create();
            $refresh->setUsername($user->getUserIdentifier());
            $refresh->setRefreshToken(bin2hex(random_bytes(32)));
            $refresh->setValid((new \DateTime())->modify('+1 month'));
            $this->refreshManager->save($refresh);
        }

        // 6) Respuesta AuthLogin
        $auth->success = true;
        $auth->jwt = $token;
        $auth->refreshToken = $refresh->getRefreshToken();
        $auth->user = $user;
        $auth->message = $this->translator->trans('ui.auth.logged_in_with_google');

        return $auth;
    }
}
