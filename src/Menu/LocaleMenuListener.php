<?php

declare(strict_types=1);

namespace Survos\EzBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;

final class LocaleMenuListener
{
    public function __construct(private readonly AdminUrlGenerator $urls)
    {
    }

    #[AsMenuListener(MenuSlot::NAVBAR_LANGUAGE, priority: 100)]
    public function languages(MenuEvent $event): void
    {
        $ea = $event->getOption('ea');
        if (null === $ea || 'header' !== $event->getOption('localePlacement')) {
            return;
        }
        $locales = $ea->getDashboardLocales();
        if (count($locales) < 2) {
            return;
        }
        $request = $ea->getRequest();
        $label = $request->getLocale();
        foreach ($locales as $locale) {
            if ($locale->getLocale() === $request->getLocale()) {
                $label = $locale->getName();
            }
        }
        $menu = $event->menu->addChild('locale', ['label' => $label])
            ->setExtra('translation_domain', false);
        foreach ($locales as $locale) {
            $url = (clone $this->urls)->setAll($request->attributes->get('_route_params', []))
                ->set('_locale', $locale->getLocale())
                ->setEntityId($request->attributes->get('entityId'))->generateUrl();
            $menu->addChild($locale->getLocale(), ['label' => $locale->getName(), 'uri' => $url])
                ->setCurrent($locale->getLocale() === $request->getLocale())
                ->setExtra('icon', $locale->getIcon())
                ->setExtra('translation_domain', false);
        }
    }
}
