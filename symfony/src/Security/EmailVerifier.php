<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        private ParameterBagInterface $parameterBag,
        private EntityManagerInterface $em
    ) {}

    /* ===========================================================
     * PUBLIC: EMAIL PARA BACKEND (Symfony Admin)
     * ===========================================================
     */
    public function sendBackendEmailConfirmation(UserInterface $user): void
    {
        $signature = $this->generateSignatureComponents(
            route: 'app_verify_email',
            user: $user
        );

        $signedUrl = $signature->getSignedUrl();

        $this->sendTemplatedEmail($user, $signedUrl, $signature);
    }

    /* ===========================================================
     * PUBLIC: EMAIL PARA API / APP (Mobile/WebApp)
     * ===========================================================
     */
    public function sendApiEmailConfirmation(UserInterface $user): void
    {
        $signature = $this->generateSignatureComponents(
            route: 'api_user_verify_email', // Route usada solo internamente para firmar
            user: $user
        );

        $appVerifyBase = $this->parameterBag->get('app.front_verify_url');
        if (!$appVerifyBase) {
            throw new \RuntimeException("Missing parameter 'app.front_verify_url'");
        }

        // --- Extraemos la query oficial del SignedUrl (incluye signature, expires, token)
        $parsed = parse_url($signature->getSignedUrl());
        $queryParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        // --- Sobreescribimos el ID para mayor consistencia
        $queryParams['id'] = $user->getId();

        // --- Armamos URL final hacia la app (deep link o frontend)
        $signedUrl = rtrim($appVerifyBase, '/')
            . '?' . http_build_query($queryParams);

        $this->sendTemplatedEmail($user, $signedUrl, $signature);
    }

    /* ===========================================================
     * PRIVATE: generar firma del email
     * ===========================================================
     */
    private function generateSignatureComponents(string $route, UserInterface $user)
    {
        return $this->verifyEmailHelper->generateSignature(
            $route,
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );
    }

    /* ===========================================================
     * PRIVATE: envío real del email
     * ===========================================================
     */
    private function sendTemplatedEmail(UserInterface $user, string $signedUrl, $signatureComponents): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(
                $this->parameterBag->get('app.mailer_sender_email'),
                $this->parameterBag->get('app.mailer_sender_name')
            ))
            ->to($user->getEmail())
            ->subject($this->translator->trans('ui.emails.account_confirmation_subject'))
            ->htmlTemplate('@backend/authentication/emails/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signedUrl,
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
                'user' => $user->getProfile()->getFirstName()
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(Request $request, UserInterface $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());

        $user->setVerified(true);
        $this->em->persist($user);
        $this->em->flush();
    }
}
