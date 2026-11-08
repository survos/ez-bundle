<?php

declare(strict_types=1);

namespace Survos\EzBundle\Tests;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Survos\EzBundle\Menu\ContextNavigation;
use Survos\EzBundle\Menu\EasyAdminMenus;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;
use Survos\MenuBundle\Renderer\MenuViewFactory;
use Survos\MenuBundle\Renderer\ThemeRenderer;
use Survos\MenuBundle\Service\MenuDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class MenuIntegrationTest extends TestCase
{
    private function setupMenus(): array
    {
        $stack = new RequestStack();
        $request = Request::create('/en/admin');
        $stack->push($request);
        $events = new EventDispatcher();
        $views = new MenuViewFactory(new Matcher());
        $provider = new AdminContextProvider($stack);
        $menus = new EasyAdminMenus(
            new MenuDispatcher(new MenuFactory(), $events, null, $stack),
            new ThemeRenderer(new Environment(new ArrayLoader()), $views),
            $views, $provider, $stack,
        );

        return [$menus, $events, $request, $stack, $provider];
    }

    public function testExplicitContextWinsAndNamedObjectsAreAvailable(): void
    {
        [$menus, , $request] = $this->setupMenus();
        $project = new \stdClass();
        $request->attributes->set('_ez_menu_arguments', ['project' => $project, 'id' => 42]);
        $request->attributes->set('ezMenuOptions', ['origin' => 'search']);
        $options = $menus->options(['origin' => 'collection']);
        self::assertSame($project, $options['project']);
        self::assertSame('collection', $options['origin']);
        self::assertArrayNotHasKey('id', $options);
        self::assertNull($options['entity']);
    }

    public function testSlotsBuildOnceAndNeverLeakBetweenRequests(): void
    {
        [$menus, $events, , $stack] = $this->setupMenus();
        $calls = 0;
        $events->addListener(MenuSlot::NAVBAR_END, static function (MenuEvent $event) use (&$calls): void {
            ++$calls;
            $event->menu->addChild('recent', ['uri' => '/recent']);
        });
        self::assertTrue($menus->hasItems(MenuSlot::NAVBAR_END));
        self::assertTrue($menus->hasItems(MenuSlot::NAVBAR_END));
        self::assertSame(1, $calls);
        $stack->push(Request::create('/fr/admin'));
        self::assertTrue($menus->hasItems(MenuSlot::NAVBAR_END));
        self::assertSame(2, $calls);
    }

    public function testPageNavigationHidesIndexOnlyWhenThereAreVisibleItems(): void
    {
        foreach ([false, true] as $visible) {
            [$menus, $events, , , $provider] = $this->setupMenus();
            $events->addListener(MenuSlot::PAGE_NAV, static function (MenuEvent $event) use ($visible): void {
                $event->menu->addChild('return', ['uri' => '/collection'])->setDisplay($visible);
            });
            $actions = Actions::new()->add(Crud::PAGE_DETAIL, Action::INDEX);
            (new ContextNavigation($menus, $provider))->configure($actions);
            self::assertSame(!$visible, $actions->getAsDto(null)->getAction(Crud::PAGE_DETAIL, Action::INDEX)->isDisplayed());
        }
    }

    public function testAlreadyHiddenIndexActionStaysHidden(): void
    {
        [$menus, , , , $provider] = $this->setupMenus();
        $actions = Actions::new()->add(Crud::PAGE_DETAIL, Action::new(Action::INDEX, 'Index')->linkToCrudAction(Action::INDEX)->displayIf(fn () => false));
        (new ContextNavigation($menus, $provider))->configure($actions);
        self::assertFalse($actions->getAsDto(null)->getAction(Crud::PAGE_DETAIL, Action::INDEX)->isDisplayed());
    }
}
