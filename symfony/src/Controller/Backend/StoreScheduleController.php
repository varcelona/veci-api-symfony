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

    #[Route('/{id}/edit', name: 'backend_store_schedule_ajax_update', methods: ['PATCH'])]
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

   #[Route('/{id}/delete', name: 'backend_store_schedule_ajax_delete', methods: ['DELETE'])]
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

    #[Route('/save', name: 'backend_store_schedule_ajax_save', methods: ['POST'])]
    public function saveSchedule(
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
                    '@backend/store_schedule/_row.html.twig',
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

    #[Route('/new-row-template', name: 'backend_store_schedule_new_row_template', methods: ['GET'])]
        public function newRowTemplate(): Response
        {
            return $this->render('@backend/store_schedule/_row_new.html.twig');
        }
}
