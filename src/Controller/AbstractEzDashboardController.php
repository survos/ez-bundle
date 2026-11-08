<?php

declare(strict_types=1);

namespace Survos\EzBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Survos\EzBundle\Menu\ContextNavigation;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractEzDashboardController extends AbstractDashboardController
{
    private ContextNavigation $navigation;

    #[Required]
    public function setContextNavigation(ContextNavigation $navigation): void
    {
        $this->navigation = $navigation;
    }

    public function configureActions(): Actions
    {
        return $this->navigation->configure(parent::configureActions());
    }
}
