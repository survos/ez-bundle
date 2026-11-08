# EasyAdmin menu slots

Register SurvosEzBundle, SurvosMenuBundle, SurvosKitBundle and KnpMenuBundle alongside EasyAdmin.
Composer/Flex installs the dependencies; in local development install normally from Packagist,
then use the mono linker. No application layout override is needed.

The bundle extends the upstream layout through Twig blocks. It does not copy the full layout,
replace EasyAdmin's CRUD actions, or replace its existing sidebar/user menus.

## Add items with listeners

```php
use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;

#[AsMenuListener(MenuSlot::NAVBAR_END)]
public function recent(MenuEvent $event): void
{
    if (null === $event->getOption('ea')) {
        return; // these slots may also be rendered by another UI bundle
    }

    $items = $this->history->accessiblePages(); // application-owned history and access checks
    if (!$items) {
        return;
    }
    $recent = $event->menu->addChild('recent', ['label' => 'navigation.recent']);
    foreach ($items as $page) {
        $recent->addChild($page->id, ['label' => $page->title, 'uri' => $page->url])
            ->setExtra('translation_domain', false);
    }
}
```

Use listener priorities to add, reorder or remove items. Named menus still use Knp Menu item trees.
Links, dropdowns and nested groups render through EasyAdmin components. Deeper groups are flattened
inside the dropdown instead of introducing unsupported fly-out menus. Hidden items, empty dropdowns,
and stray dividers are removed by menu-bundle before rendering. Menu visibility is not route authorization.

## Regions

| Slot | Placement |
| --- | --- |
| `NAVBAR_START`, `NAVBAR_LANGUAGE`, `NAVBAR_END` | Shared desktop tools and mobile second row |
| `BANNER` | After flash messages |
| `BREADCRUMB`, `PAGE_NAV` | Before the page heading |
| `PAGE_ACTIONS` | Alongside EasyAdmin page actions |
| `SIDEBAR` | After the existing sidebar menu |
| `FOOTER`, `FOOTER_END` | After the content footer |

Empty slots have no menu markup. Desktop and mobile reuse the same events and request cache.
Render additional menus with `ez_menu('card.actions', {project: project})`. The explicit EasyAdmin theme
is isolated from Tabler's spec providers, so both integrations can coexist in one application.

## Context

Every listener invoked through `ez_menu()` receives:

- `ea`: current EasyAdmin context, or null outside an EasyAdmin request;
- `entity`: current entity instance, or null on dashboard/index pages;
- `pageName`: CRUD page name, or null outside CRUD;
- `route` and `routeParameters`;
- named controller arguments that are objects;
- `localePlacement`.

Set request-scoped application context before rendering:

```php
$request->attributes->set('ezMenuOptions', ['project' => $project, 'origin' => 'collection']);
```

Precedence, lowest first: named object arguments, EasyAdmin context, `ezMenuOptions`, explicit
`ez_menu()` options. Scalars are not automatically copied from controller arguments. Pass template-only
objects explicitly. No menu state is shared between requests. Locale links retain CRUD identity and
list query parameters using EasyAdmin's URL generator.

## Contextual navigation instead of Back to listing

Extend `Survos\EzBundle\Controller\AbstractEzDashboardController` and retain
`parent::configureActions()` if you override that method. Existing EZ CRUD base controllers also wire
this behavior. Alternatively inject `Menu\ContextNavigation` and pass your configured `Actions`
through `configure()` at the end of your dashboard/CRUD action configuration.

A visible `PAGE_NAV` menu suppresses the built-in index action on detail/edit pages. Without visible
items, the original action remains. Existing display conditions are respected, and index routes are
never disabled. Populate `PAGE_NAV` with application-owned destinations such as a collection, a
project overview, or a search route with validated parameters. This integration does not consume an
untrusted `returnTo` URL or infer browser history.

For this automatic action suppression, set context in controller arguments or `ezMenuOptions` before
EasyAdmin processes actions; template-only options supplied later cannot change an already built action.

## Locale placement

The default is a standalone header language menu. To retain the upstream Settings placement:

```yaml
survos_ez:
    localePlacement: settings
```

Header mode reads `Dashboard::setLocales()`. It shows a selector for two or more locales and leaves
appearance controls in Settings. Settings mode uses the upstream settings menu unchanged. The small
appearance partial is the remaining upstream-coupled template: check it when upgrading EasyAdmin.

## Validation

Run `vendor/bin/phpunit -c phpunit.xml.dist` in a standalone checkout. For a linked consuming app:

```bash
EZ_TEST_AUTOLOAD=/path/to/app/vendor/autoload.php php /path/to/app/vendor/bin/phpunit \
  --no-configuration --bootstrap tests/bootstrap.php tests --do-not-cache-result
```

The local official demo exercises actual recent-page history, standalone locale selection, a UI Kit
page action and category navigation. Application history storage is intentionally outside this bundle.
