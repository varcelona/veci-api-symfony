<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthCodeMailer implements AuthCodeMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private \Twig\Environment $twig,
        private ParameterBagInterface $parameterBag,
        private TranslatorInterface $translator
    ){
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->parameterBag->get('app.mailer_sender_email'), $this->parameterBag->get('app.mailer_sender_name')))
            ->to($user->getEmail())
            ->subject($this->translator->trans('ui.emails.auth_code_subject'))
            ->htmlTemplate('@backend/authentication/emails/2fa_code.html.twig')
            ->context([
                'authCode' => $user->getEmailAuthCode(),
                'user' => $user->getProfile()->getFirstName()
            ]);

        $this->mailer->send($email);
    }
}