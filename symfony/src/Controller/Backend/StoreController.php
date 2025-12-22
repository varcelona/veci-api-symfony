<?php

namespace App\Controller\Backend;

use App\Entity\Store;
use App\Entity\StoreSchedule;
use App\Form\StoreType;
use App\Repository\StoreRepository;
use App\Repository\StoreScheduleRepository;
use App\Security\Voter\StoreScheduleVoter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/store')]
class StoreController extends AbstractController
{
    #[Route('/', name: 'backend_store_index', methods: ['GET'])]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        StoreRepository $storeRepository
    ): Response {
        $this->denyAccessUnlessGrantedAny(['ROLE_ADMIN', 'ROLE_MERCHANT']);

        $string = $request->query->get('search');

        $pagination = $paginator->paginate(
            $storeRepository->findByString($string),
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('@backend/crud/index.html.twig', [
            'config' => ['entity' => 'store', 'icon' => 'shop'],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'backend_store_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        StoreRepository $storeRepository
    ): Response {
        $this->denyAccessUnlessGrantedAny(['ROLE_ADMIN', 'ROLE_MERCHANT']);

        $store = new Store();
        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeRepository->save($store, true);

            return $this->redirectToRoute('backend_store_index');
        }

        return $this->render('@backend/crud/new.html.twig', [
            'config' => ['entity' => 'store', 'icon' => 'shop'],
            'entity' => $store,
            'form' => $form,
            'isNew' => true
        ]);
    }

    #[Route('/edit/{id}', name: 'backend_store_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Store $store,
        StoreRepository $storeRepository
    ): Response {
        $this->denyAccessUnlessGrantedAny(['ROLE_ADMIN', 'ROLE_MERCHANT']);

        $form = $this->createForm(StoreType::class, $store);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $storeRepository->save($store, true);

            return $this->redirectToRoute('backend_store_index');
        }

        return $this->render('@backend/crud/edit.html.twig', [
            'config' => ['entity' => 'store', 'icon' => 'shop'],
            'entity' => $store,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'backend_store_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Store $store,
        StoreRepository $storeRepository
    ): Response {
        $this->denyAccessUnlessGrantedAny(['ROLE_ADMIN', 'ROLE_MERCHANT']);

        if ($this->isCsrfTokenValid('delete'.$store->getId(), $request->request->get('_token'))) {
            $storeRepository->remove($store, true);
        }

        return $this->redirectToRoute('backend_store_index');
    }

    /**
     * Helper: permite acceso si tiene AL MENOS uno de los roles
     */
    private function denyAccessUnlessGrantedAny(array $roles): void
    {
        foreach ($roles as $role) {
            if ($this->isGranted($role)) {
                return;
            }
        }

        throw $this->createAccessDeniedException();
    }

    #[Route('/{store}/schedule/modal', name: 'backend_store_schedule_modal')]
    public function modal(
        Store $store,
        StoreScheduleRepository $repository
    ): Response {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $store
        );

        return $this->render('@backend/store_schedule/_modal_list.html.twig', [
            'store' => $store,
            'schedules' => $repository->findBy(
                ['store' => $store],
                ['weekDay' => 'ASC']
            ),
        ]);
    }
}
