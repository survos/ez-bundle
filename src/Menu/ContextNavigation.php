<?php

declare(strict_types=1);

namespace Survos\EzBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Survos\MenuBundle\MenuSlot;

final class ContextNavigation
{
    public function __construct(private readonly EasyAdminMenus $menus, private readonly AdminContextProvider $contexts)
    {
    }

    public function configure(Actions $actions): Actions
    {
        foreach ([Crud::PAGE_DETAIL, Crud::PAGE_EDIT] as $page) {
            if (null !== $actions->getAsDto(null)->getAction($page, Action::INDEX)) {
                $original = clone $actions->getAsDto(null)->getAction($page, Action::INDEX);
                $actions->update($page, Action::INDEX, fn (Action $action): Action => $action
                    ->displayIf(fn (): bool => $original->isDisplayed($this->contexts->getContext()?->getEntity()) && !$this->menus->hasItems(MenuSlot::PAGE_NAV)));
            }
        }

        return $actions;
    }
}
