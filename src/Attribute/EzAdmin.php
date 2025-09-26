<?php
declare(strict_types=1);

namespace Survos\EzBundle\Attribute;

use Attribute;

/**
 * Entity-level admin metadata.
 * Attach to Doctrine entities to steer default CRUD behavior and UI affordances.
 *
 * Example:
 *   #[EzAdmin(label: 'Fortepan Object', icon: 'fa-regular fa-image', defaultSort: ['year' => 'DESC'], indexMax: 12)]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EzAdmin
{
    /**
     * @param array<string, 'ASC'|'DESC'|int|string> $defaultSort e.g. ['year' => 'DESC', 'id' => 'ASC']
     * @param non-empty-string[]|null                $hiddenPages e.g. [Page::NEW, Page::EDIT]
     * @param int[]|null                             $pageSizes   e.g. [25, 50, 100]
     */
    public function __construct(
        public ?string $label = null,           // If null, derive from short class name
        public ?string $icon = null,            // Font Awesome / icon key (optional)
        public ?string $crudController = null,  // Optional custom CRUD controller FQCN
        public ?array  $defaultSort = null,     // Default sorting for INDEX
        public ?int    $indexMax = null,        // Preferred max fields in INDEX (replaces EA's hard-coded 7)
        public ?array  $hiddenPages = null,     // Hide pages entirely (by Page::*)
        public ?array  $pageSizes = null,       // Paginator page sizes preference
    ) {}
}
