<?php

namespace App\Controller;

use App\Form\LoginUserFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private Security $security,
        private CodeGeneratorInterface $codeGenerator,
        private TranslatorInterface $translator
    ) {
    }

    #[Route('/admin/login', name: 'backend_login')]
    public function index(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator, Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('backend_dashboard');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {
            $this->addFlash('warning', $translator->trans($error->getMessage(), [], 'security'));
        }

        $form = $this->createForm(LoginUserFormType::class);
        $form->handleRequest($request);

        return $this->render('@backend/authentication/login/index.html.twig', [
             'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/verify-email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        $id = $request->get('id'); // retrieve the user id from the url

        // Verify the user id exists and is not null
        if (null === $id) {
            $this->addFlash('warning', 'Your validation link is invalid.');
            return $this->redirectToRoute('backend_login');
        }

        $user = $userRepository->find($id);

        // Ensure the user exists in persistence
        if (null === $user) {
            $this->addFlash('warning', 'Your validation link is invalid.');
            return $this->redirectToRoute('backend_login');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('danger', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('backend_login');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', $this->translator->trans(
            'message.account_confirmation_success'
        ));

        $this->security->login($user, 'form_login');

        return $this->redirectToRoute('backend_login');
    }


    #[Route('/admin/logout', name: 'backend_logout', methods: ['GET'], priority: 10)]
    public function logout():  void
    {
        return;
    }

    #[Route('/admin/2fa-resend', name: 'backend_2fa_resend', methods: ['GET'], priority: 10)]
    public function resendCode(): Response
    {
        $user = $this->security->getUser();

        if ($user instanceof TwoFactorInterface) {
            $this->codeGenerator->generateAndSend($user);

            $this->addFlash('success', $this->translator->trans('auth_code_resended', [], 'security'));

            return $this->redirectToRoute('2fa_login');
        }

        $this->addFlash('warning', $this->translator->trans('auth_code_user_invalid', [], 'security'));

        return $this->redirectToRoute('2fa_login');
    }
}
