<?php

declare(strict_types=1);

namespace Survos\EzBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Survos\MenuBundle\Renderer\MenuViewFactory;
use Survos\MenuBundle\Renderer\ThemeRenderer;
use Survos\MenuBundle\Service\MenuDispatcher;
use Survos\MenuBundle\Service\Menus;
use Symfony\Component\HttpFoundation\RequestStack;

final class EasyAdminMenus
{
    private readonly Menus $menus;

    public function __construct(
        MenuDispatcher $dispatcher,
        ThemeRenderer $renderer,
        MenuViewFactory $views,
        private readonly AdminContextProvider $contexts,
        private readonly RequestStack $requests,
        public readonly string $localePlacement = 'header',
    ) {
        // Share events and request memoization, but never inherit another UI bundle's themes.
        $this->menus = new Menus($dispatcher, $renderer, $views);
    }

    public function options(array $options = []): array
    {
        $ea = $this->contexts->getContext();
        $request = $this->requests->getCurrentRequest();
        $arguments = array_filter($request?->attributes->get('_ez_menu_arguments', []) ?? [], is_object(...));

        return array_replace($arguments, [
            'ea' => $ea,
            'entity' => null !== $ea?->getCrud() ? $ea->getEntity()->getInstance() : null,
            'pageName' => $ea?->getCrud()?->getCurrentPage(),
            'route' => $request?->attributes->get('_route'),
            'routeParameters' => $request?->attributes->get('_route_params', []) ?? [],
            'localePlacement' => $this->localePlacement,
        ], $request?->attributes->get('ezMenuOptions', []) ?? [], $options);
    }

    public function render(string $slot, array $options = [], array $render = []): string
    {
        return $this->menus->render($slot, $this->options($options), array_replace([
            'themes' => ['@SurvosEz/menu/theme.html.twig'],
            'slot' => $slot,
        ], $render));
    }

    public function hasItems(string $slot, array $options = []): bool
    {
        return $this->menus->hasItems($slot, $this->options($options));
    }
}
