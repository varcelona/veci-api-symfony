<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\Voter\UserVerifyVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

#[Route('/api/user')]
class ApiUserVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier
    ) {}

    #[Route('/verify-email', name: 'api_user_verify_email', methods: ['POST'])]
    public function apiVerifyEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $id        = $request->request->get('id');
        $expires   = $request->request->get('expires');
        $signature = $request->request->get('signature');
        $token     = $request->request->get('token'); // opcional pero importante

        if (!$id || !$expires || !$signature) {
            return new JsonResponse(['success' => false, 'message' => 'Missing verification data'], 400);
        }

        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }

        // 🧠 Reconstruimos la URL EXACTA que Symfony hubiera firmado
        $frontendVerifyUrl = rtrim($this->getParameter('app.front_verify_url'), '/');

        $params = [
            'id'        => $id,
            'expires'   => $expires,
            'signature' => $signature,
        ];

        if ($token) {
            $params['token'] = $token;
        }

        $builtUrl = $frontendVerifyUrl . '?' . http_build_query($params);

        // Creamos request simulado con la URL completa (lo que necesita validateEmailConfirmation)
        $fakeRequest = Request::create($builtUrl);

        try {
            $this->emailVerifier->handleEmailConfirmation($fakeRequest, $user);
        } catch (VerifyEmailExceptionInterface $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getReason()], 400);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Email verified',
            'userId'  => $user->getId()
        ]);
    }

}
