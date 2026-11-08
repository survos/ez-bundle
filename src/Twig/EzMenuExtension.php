<?php

declare(strict_types=1);

namespace Survos\EzBundle\Twig;

use Survos\EzBundle\Menu\EasyAdminMenus;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EzMenuExtension extends AbstractExtension
{
    public function __construct(private readonly EasyAdminMenus $menus)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ez_menu', $this->menus->render(...), ['is_safe' => ['html']]),
            new TwigFunction('ez_menu_has_items', $this->menus->hasItems(...)),
            new TwigFunction('ez_locale_placement', fn (): string => $this->menus->localePlacement),
        ];
    }
}
