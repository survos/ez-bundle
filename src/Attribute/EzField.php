<?php
declare(strict_types=1);

namespace Survos\EzBundle\Attribute;

use Attribute;

/**
 * Property-level admin metadata.
 *
 * Example:
 *   #[EzField(index: true, order: 10, filter: true, label: 'Title', linkRoute: 'admin_app_product_show', linkParam: 'productId')]
 *   public ?string $title = null;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EzField
{
    /**
     * @param non-empty-string[]|null $showOn
     * @param non-empty-string[]|null $hideOn
     */
    public function __construct(
        // Visibility & placement
        public bool   $index = false,
        public ?int   $order = null,
        public bool   $filter = false,
        public ?bool  $sortable = null,

        // Presentation
        public ?string $label = null,
        public ?string $help = null,
        public ?string $width = null,
        public ?string $format = null,

        // Rendering strategy
        public ?string $fieldClass = null,
        public ?string $templatePath = null,
        public ?string $type = null,

        // Page targeting
        public ?array $showOn = null,
        public ?array $hideOn = null,

        // Filtering config
        public ?string $filterType = null,
        public ?array  $filterOptions = null,

        // Linking (cell -> route)
        public ?string $linkRoute = null,      // Symfony route name
        public ?string $linkParam = null,      // route param name (default 'id')
        public ?string $linkProperty = null,   // entity property used for param (default primary key)
    ) {}
}
