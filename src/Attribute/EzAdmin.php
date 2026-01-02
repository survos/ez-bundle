<?php
declare(strict_types=1);

namespace Survos\EzBundle\Attribute;

use Attribute;

/**
 * Entity-level admin metadata.
 *
 * Example:
 *   #[EzAdmin(
 *      label: 'Product',
 *      defaultSort: ['price' => 'DESC'],
 *      indexMax: 12,
 *      editRoles: ['ROLE_ADMIN','ROLE_EDITOR'],
 *      readOnlyByDefault: true
 *   )]
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EzAdmin
{
    /**
     * @param array<string, 'ASC'|'DESC'|int|string>|null $defaultSort
     * @param non-empty-string[]|null                    $hiddenPages
     * @param int[]|null                                 $pageSizes
     * @param non-empty-string[]|null                    $editRoles Roles allowed to mutate (NEW/EDIT/DELETE)
     */
    public function __construct(
        public ?string $label = null,
        public ?string $icon = null,
        public ?string $crudController = null,
        public ?array  $defaultSort = null,
        public ?int    $indexMax = null,
        public ?array  $hiddenPages = null,
        public ?array  $pageSizes = null,

        // Security/UX defaults
        public ?array $editRoles = null,
        public bool $readOnlyByDefault = true,
    ) {}
}
