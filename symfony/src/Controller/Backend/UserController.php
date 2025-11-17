<?php

namespace App\Controller\Backend;

use App\Domain\User\UserCreationManager;
use App\Entity\User;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Security\EmailVerifier;

#[Route('/admin/user')]
class UserController extends AbstractController
{

    public function __construct(
        private TranslatorInterface $translator,
        private EmailVerifier $emailVerifier,
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    #[Route('/', name: 'backend_user_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator, UserRepository $userRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('backend_user_edit', ['id' => $this->getUser()], Response::HTTP_SEE_OTHER);
        }

        $string = $request->query->get('search');

        $pagination = $paginator->paginate(
            $userRepository->findByString($string),
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('@backend/crud/index.html.twig', [
            'config' => ['entity' => 'user', 'icon' => 'people-fill'],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'backend_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher, UserCreationManager $userCreationManager): Response
    {
        $this->denyAccessUnlessGrantedAny([UserVoter::MANAGE_ALL_USERS]);

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // 🔥 crear merchant/customer según rol
            $userCreationManager->handleUserRoleSideEffects($user);

            $userRepository->save($user, true);

            return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('@backend/crud/new.html.twig', [
            'config' => ['entity' => 'user', 'icon' => 'people-fill'],
            'entity' => $user,
            'form' => $form,
            'isNew' => true
        ]);
    }

    #[Route('/edit/{id}', name: 'backend_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserRepository $userRepository, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $current = $this->getUser();

        if ($current instanceof User && $current->getId() === $user->getId()) {
            $this->denyAccessUnlessGranted(UserVoter::MANAGE_SELF_LIMITED, $user);
        } else {
            if (
                !$this->isGranted(UserVoter::MANAGE_ALL_USERS, $user)
            ) {
                throw $this->createAccessDeniedException();
            }
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            if ($form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            if (
                !$this->isGranted(UserVoter::MANAGE_ALL_USERS) &&
                $current instanceof User
            ) {
                throw $this->createAccessDeniedException();
            }

            // Evita modificaciones ilegales
            if ($this->isGranted(UserVoter::MANAGE_SELF_LIMITED, $user)
                && !$this->isGranted(UserVoter::MANAGE_ALL_USERS, $user)) {
                $originalUser = $userRepository->find($user->getId());

                $user->setRoles($originalUser->getRoles());
            }

            $userRepository->save($user, true);

            return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('@backend/crud/edit.html.twig', [
            'config' => ['entity' => 'user', 'icon' => 'people-fill'],
            'entity' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'backend_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user, true);
        }

        // Debe poder gestionar usuarios
        if (
            !$this->isGranted(UserVoter::MANAGE_ALL_USERS, $user)
        ) {
            throw $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/suspend/{id}', name: 'backend_user_suspend', methods: ['GET'])]
    public function suspend(
        Request $request,
        User $user,
        UserRepository $userRepository
    ): Response {
        if (!$this->isCsrfTokenValid('suspend'.$user->getId(), $request->query->get('_token'))) {
            return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // Permisos análogos a delete
        if (
            !$this->isGranted(UserVoter::MANAGE_ALL_USERS, $user)
        ) {
            throw $this->createAccessDeniedException();
        }

        // Forzamos logout de la sesión actual (si aplica)
        $logoutEvent = new LogoutEvent($request, $this->tokenStorage->getToken());
        $this->eventDispatcher->dispatch($logoutEvent);
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('warning', $this->translator->trans('message.account_blocked', [
            'email' => $user->getEmail()
        ]));

        $user->setEnabled(false);
        $user->setVerified(false);
        $userRepository->save($user, true);

        return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
    }


     #[Route('/verify/{id}', name: 'backend_user_verify', methods: ['GET'])]
    public function verify(Request $request, User $user): Response
    {
        // Verificar cuenta: normalmente reservado a global
        $this->denyAccessUnlessGranted(UserVoter::MANAGE_ALL_USERS, $user);

        $this->emailVerifier->sendEmailConfirmation($user);

        $this->addFlash('success', $this->translator->trans('message.account_confirmation_sent', [
            'email' => $user->getEmail()
        ]));

        return $this->redirectToRoute('backend_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /** Helper: niega si no tiene ninguno de los atributos dados */
    private function denyAccessUnlessGrantedAny(array $attributes, mixed $subject = null): void
    {
        foreach ($attributes as $attr) {
            if ($this->isGranted($attr, $subject)) {
                return;
            }
        }
        throw $this->createAccessDeniedException();
    }
}
