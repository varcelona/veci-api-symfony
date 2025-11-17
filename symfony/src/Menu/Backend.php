<?php

namespace App\Menu;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final class Backend
{
    public function __construct(
      private FactoryInterface $factory,
      private RequestStack $requestStack,
      private Security $security,
      private TranslatorInterface $translator,
      private EntityManagerInterface $entityManager
    ) {
    }

    public function createBackendAdminMenu(array $options): ItemInterface
    {
      $request = $this->requestStack->getCurrentRequest();

      $menu = $this->factory->createItem('root', ['route' => 'backend_dashboard'])
        ->setLabel($this->translator->trans('menu.dashboard'))
        ->setAttributes(['class' => 'accordion accordion-flush', 'id' => 'backendMenuAccordion']);

      $menu
        ->addChild('menu-top')
        ->setAttributes(['class' => 'd-none d-lg-block text-secondary', 'role' => 'divider']);

      $menu
        ->addChild('menu-middle')
        ->setAttributes(['class' => 'd-none d-lg-block text-secondary', 'role' => 'divider']);

      $menu
        ->addChild('admin_dashboard', ['route' => 'backend_dashboard'])
        ->setAttributes(['class' => 'menu-link menu-header'])
        ->setLinkAttributes(['class' => 'menu-link px-4'])
        ->setLabel($this->translator->trans('menu.dashboard'));


      $menu
        ->addChild('menu-users')
        ->setAttributes(['class' => 'd-none d-lg-block text-secondary', 'role' => 'divider']);

      // Usuarios section
      // if ($this->security->isGranted('MANAGE_ALL_USERS')) {
        $menu
          ->addChild('backend_user', ['route' => 'backend_user_index'])
          ->setAttributes(['class' => 'accordion-item'])
          ->setChildrenAttributes(['class' => 'accordion-collapse collapse', 'id' => 'usersAccordion', 'data-bs-parent' => '#backendMenuAccordion'])
          ->setLabel($this->translator->trans('menu.user.plural'))
          ->setExtras(['menuTitle' => '1', 'icon' => 'bi-people', 'attributes' => ['class' => 'accordion-header accordion-button collapsed', 'data-bs-target' => "#usersAccordion", 'data-bs-toggle' => "collapse", 'role' => "button", 'aria-expanded' => "false", 'aria-controls' => "usersAccordion"]]);

        $menu['backend_user']
          ->addChild('backend_user_index', ['route' => 'backend_user_index'])
          ->setAttributes(['class' => 'menu-item'])
          ->setLinkAttributes(['class' => 'menu-link'])
          ->setLabel($this->translator->trans('menu.user.all'));

        $menu['backend_user']
          ->addChild('backend_user_new', ['route' => 'backend_user_new'])
          ->setAttributes(['class' => 'menu-item'])
          ->setLinkAttributes(['class' => 'menu-link'])
          ->setLabel($this->translator->trans('menu.user.new'));

        if ($request->get('id')) {
          $menu['backend_user']
            ->addChild('backend_user_edit', ['route' => 'backend_user_edit', 'routeParameters' => array('id' => $request->get('id'))])
            ->setAttributes(['class' => 'menu-item'])
            ->setLinkAttributes(['class' => 'menu-link d-none'])
            ->setLabel($this->translator->trans('menu.user.edit'));
        }
      // }

      return $menu;
    }


    public function createLayoutTabsMenu(array $options): ItemInterface
    {

      $entity = $this->entityManager->getClassMetadata(get_class($options['entity']))->discriminatorValue;
      $request = $this->requestStack->getCurrentRequest();
      $menu = $this->factory->createItem('root', ['route' => sprintf('backend_%s_index', $entity)])
        ->setAttributes(['class' => 'nav nav-tabs me-5 mb-4']);

      if ($this->security->isGranted('MANAGE_LAYOUT_PAGES') || $this->security->isGranted('MANAGE_LAYOUT_PROGRAMS')) {
        $menu
          ->addChild('backend_layout_index', ['route' => sprintf('backend_%s_index', $entity)])
          ->setAttributes(['class' => 'nav-item'])
          ->setLinkAttributes(['class' => 'nav-link'])
          ->setLabel($this->translator->trans('menu.layout.all'));
      }

      if ($request->get('id')) {
        $menu
          ->addChild('backend_layout_edit', ['route' => sprintf('backend_%s_edit', $entity), 'routeParameters' => array('id' => $request->get('id'))])
          ->setAttributes(['class' => 'nav-item'])
          ->setLinkAttributes(['class' => 'nav-link'])
          ->setLabel($this->translator->trans('menu.layout.edit'));

        $menu
          ->addChild('backend_layout_layout', ['route' => sprintf('backend_%s_layout', $entity), 'routeParameters' => array('id' => $request->get('id'))])
          ->setAttributes(['class' => 'nav-item'])
          ->setLinkAttributes(['class' => 'nav-link'])
          ->setLabel($this->translator->trans('menu.layout.layout'));

        $menu
          ->addChild('backend_layout_seo', ['route' => sprintf('backend_%s_seo', $entity), 'routeParameters' => array('id' => $request->get('id'))])
          ->setAttributes(['class' => 'nav-item'])
          ->setLinkAttributes(['class' => 'nav-link'])
          ->setLabel($this->translator->trans('menu.layout.seo'));
      }

      return $menu;
    }
}