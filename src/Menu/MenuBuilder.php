<?php

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class MenuBuilder
{
    private Security $security;

    private FactoryInterface $factory;

    public function __construct(Security $security, FactoryInterface $factory)
    {
        $this->security = $security;
        $this->factory = $factory;
    }

    public function createLeftMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'navbar-nav mr-auto');

        if ($user = $this->security->getUser()) {
            $this->createLeftUserMenu($menu, $user);
        } else {
            $this->createLeftGuestMenu($menu);
        }

        foreach ($menu as $child) {
            $child->setLinkAttribute('class', 'nav-link')
                ->setAttribute('class', 'nav-item');
        }

        return $menu;
    }

    public function createRightMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'navbar-nav ml-auto');

        if ($user = $this->security->getUser()) {
            $this->createRightUserMenu($menu, $user);
        } else {
            $this->createRightGuestMenu($menu);
        }

        foreach ($menu as $child) {
            $child->setLinkAttribute('class', 'nav-link');
        }

        return $menu;
    }

    private function createLeftUserMenu(ItemInterface $menu, UserInterface $user): void
    {
    }

    private function createLeftGuestMenu(ItemInterface $menu): void
    {

    }

    private function createRightUserMenu(ItemInterface $menu, UserInterface $user): void
    {
        $item = $menu->addChild($user->getUsername(), [
            'attributes' => [
                'class' => 'nav-item dropdown',
            ],
            'linkAttributes' => [
                'class' => 'nav-link dropdown-toggle',
                'role'  => 'button',
                'data-toggle' => 'dropdown',
            ],
            'uri' => '#',
        ]);

        $item->addChild('Logout', [
            'route' => 'fos_user_security_logout',
            'attributes' => [
                'icon' => 'fa fa-sign-out',
            ],
        ]);
    }

    private function createRightGuestMenu(ItemInterface $menu): void
    {
        $menu->addChild('Sign in', ['route' => 'fos_user_security_login']);
    }
}