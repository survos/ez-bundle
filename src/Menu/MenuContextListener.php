<?php

declare(strict_types=1);

namespace Survos\EzBundle\Menu;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

final class MenuContextListener
{
    #[AsEventListener]
    public function captureArguments(ControllerArgumentsEvent $event): void
    {
        $event->getRequest()->attributes->set('_ez_menu_arguments', $event->getNamedArguments());
    }
}
