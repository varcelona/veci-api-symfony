<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Mime\Address;

class PasswordResetMailer
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerInterface $mailer,
        private ParameterBagInterface $params
    ) {}

    public function sendResetEmail(User $user): void
    {
        $appResetBase = $this->params->get('app.password_reset_url');

        if (!$appResetBase) {
            throw new \RuntimeException('APP_PASSWORD_RESET_URL is not defined.');
        }

        $resetToken = $this->resetPasswordHelper->generateResetToken($user);

        // URL que va a la APP, NO al backend
        $resetUrl = sprintf(
            '%s?token=%s',
            rtrim($appResetBase, '/'),
            $resetToken->getToken()
        );

        $email = (new TemplatedEmail())
            ->from(new Address(
                $this->params->get('app.mailer_sender_email'),
                $this->params->get('app.mailer_sender_name')
            ))
            ->to($user->getEmail())
            ->subject('Reset your password')
            ->htmlTemplate('@backend/authentication/emails/reset_password.html.twig')
            ->context([
                'resetUrl' => $resetUrl,
                'token' => $resetToken,
                'user' => $user->getProfile()?->getFirstName()
            ]);

        $this->mailer->send($email);
    }
}
