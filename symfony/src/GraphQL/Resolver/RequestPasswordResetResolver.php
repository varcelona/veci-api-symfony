<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\GraphQL\Resource\Auth;
use App\Repository\UserRepository;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RequestPasswordResetResolver implements MutationResolverInterface
{
    public function __construct(
        private UserRepository $users,
        private ResetPasswordHelperInterface $resetHelper,
        private MailerInterface $mailer,
        private ParameterBagInterface $parameterBag,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {}

    public function __invoke(?object $item, array $context): ?object
    {
        $email = $context['args']['input']['email'] ?? null;

        // siempre devolvemos un Auth
        $auth = $item instanceof Auth ? $item : new Auth();
        $auth->success = false;
        $auth->message = $this->translator->trans('ui.auth.invalid_email');

        if (!$email) {
            return $auth;
        }

        $user = $this->users->findOneBy(['email' => $email]);

        // No revelamos si existe o no: si no existe, “todo OK igual”
        if (!$user) {
            $auth->success = true;
            $auth->message = $this->translator->trans('ui.auth.reset_link_sent');
            return $auth;
        }

        try {
            $resetToken = $this->resetHelper->generateResetToken($user);

            $appRequestBase = $this->parameterBag->get('app.front_reset_password_url');
            if (!$appRequestBase) {
                $auth->message = 'Missing parameter Reset Password URL.';
                throw new \RuntimeException("Missing parameter 'app.front_reset_password_url'");
                return $auth;
            }

            $resetUrl = $appRequestBase. '?token=' . urlencode($resetToken->getToken());

            $message = (new TemplatedEmail())
                ->from(new Address($this->parameterBag->get('app.mailer_sender_email'), $this->parameterBag->get('app.mailer_sender_name')))
                ->to($email)
                ->subject($this->translator->trans('ui.emails.reset_password_subject'))
                ->htmlTemplate('@backend/authentication/emails/reset_password.html.twig')
                ->context([
                    'resetUrl' => $resetUrl,
                    'resetToken' => $resetToken,
                    'user' => $user->getProfile()?->getFirstName(),
                ]);

            $this->mailer->send($message);

            $auth->success = true;
            $auth->message = $this->translator->trans('ui.auth.reset_link_sent');
        } catch (\Throwable $e) {
            $this->logger->error('Error generating reset password token', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $auth->success = false;
            $auth->message = $this->translator->trans('ui.auth.could_not_process');
        }

        return $auth;
    }
}
