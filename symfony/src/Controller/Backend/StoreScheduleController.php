<?php

namespace App\Controller\Backend;

use App\Entity\Store;
use App\Entity\StoreSchedule;
use App\Form\StoreScheduleType;
use App\Repository\StoreScheduleRepository;
use App\Security\Voter\StoreScheduleVoter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/store/{store}/schedule')]
class StoreScheduleController extends AbstractController
{
    #[Route('/', name: 'backend_store_schedule_index', methods: ['GET'])]
    public function index(
        Store $store,
        Request $request,
        PaginatorInterface $paginator,
        StoreScheduleRepository $repository
    ): Response {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $store
        );

        $pagination = $paginator->paginate(
            $repository->findBy(['store' => $store], ['weekDay' => 'ASC']),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('@backend/store_schedule/_index.html.twig', [
            'store' => $store,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'backend_store_schedule_new', methods: ['GET', 'POST'])]
    public function new(
        Store $store,
        Request $request,
        StoreScheduleRepository $repository
    ): Response {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $store
        );

        $schedule = new StoreSchedule();
        $schedule->setStore($store);

        $form = $this->createForm(StoreScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($schedule, true);

            return $this->redirectToRoute('backend_store_schedule_index', [
                'store' => $store->getId(),
            ]);
        }

        return $this->render('@backend/store_schedule/_form.html.twig', [
            'store' => $store,
            'form' => $form,
            'isNew' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'backend_store_schedule_edit', methods: ['GET', 'POST'])]
    public function edit(
        Store $store,
        StoreSchedule $schedule,
        Request $request,
        StoreScheduleRepository $repository
    ): Response {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $store
        );

        // Seguridad extra: evita edición cruzada por URL
        if ($schedule->getStore() !== $store) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(StoreScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($schedule, true);

            return $this->redirectToRoute('backend_store_schedule_index', [
                'store' => $store->getId(),
            ]);
        }

        return $this->render('@backend/store_schedule/_form.html.twig', [
            'store' => $store,
            'form' => $form,
            'isNew' => false,
        ]);
    }

    #[Route('/{id}/delete', name: 'backend_store_schedule_delete', methods: ['POST'])]
    public function delete(
        Store $store,
        StoreSchedule $schedule,
        Request $request,
        StoreScheduleRepository $repository
    ): Response {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $store
        );

        if ($schedule->getStore() !== $store) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete'.$schedule->getId(), $request->request->get('_token'))) {
            $repository->remove($schedule, true);
        }

        return $this->redirectToRoute('backend_store_schedule_index', [
            'store' => $store->getId(),
        ]);
    }


    #[Route('/{id}/ajax-update', name: 'backend_store_schedule_ajax_update', methods: ['PATCH'])]
    public function updateInline(
        StoreSchedule $schedule,
        Request $request,
        StoreScheduleRepository $repo
    ): Response {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $schedule->getStore()
        );

        $data = json_decode($request->getContent(), true);

        $schedule
            ->setOpen((bool) $data['open'])
            ->setTimeFrom($data['from'] ? new \DateTime($data['from']) : null)
            ->setTimeTo($data['to'] ? new \DateTime($data['to']) : null);

        $repo->save($schedule, true);

        return $this->json(['ok' => true]);
    }

   #[Route('/{id}/ajax-delete', name: 'backend_store_schedule_ajax_delete', methods: ['DELETE'])]
    public function deleteSchedule(
        StoreSchedule $schedule,
        StoreScheduleRepository $repo,
        TranslatorInterface $translator
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $schedule->getStore()
        );

        try {
            $repo->remove($schedule);

            return $this->json([
                'success' => true,
                'message' => $translator->trans('ui.modal.store_schedule.delete.success'),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => $translator->trans('ui.modal.store_schedule.delete.error'),
            ], 500);
        }
    }

    #[Route('/ajax-create', name: 'backend_store_schedule_ajax_create', methods: ['POST'])]
    public function createSchedule(
        Store $store,
        Request $request,
        StoreScheduleRepository $repo,
        TranslatorInterface $translator
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            StoreScheduleVoter::MANAGE_OWN,
            $store
        );

        try {
            $data = json_decode($request->getContent(), true);

            $schedule = new StoreSchedule();
            $schedule->setStore($store);
            $schedule->setWeekDay($data['weekDay'] ?? null);
            $schedule->setOpen(true);
            $schedule->setTimeFrom(
                isset($data['from']) ? new \DateTime($data['from']) : null
            );
            $schedule->setTimeTo(
                isset($data['to']) ? new \DateTime($data['to']) : null
            );

            $repo->save($schedule, true);

            return $this->json([
                'success' => true,
                'message' => $translator->trans('store_schedule.create.success'),
                'html' => $this->renderView(
                    '@backend/store_schedule/_create.html.twig',
                    ['s' => $schedule]
                )
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'message' => $translator->trans('store_schedule.create.error'),
            ], 400);
        }
    }
}
