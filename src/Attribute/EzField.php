<?php
declare(strict_types=1);

namespace Survos\EzBundle\Attribute;

use Attribute;

/**
 * Property-level admin metadata.
 *
 * Example:
 *   #[EzField(index: true, order: 1, filter: true, label: 'Year')]
 *   public ?int $year = null;
 *
 * Notes:
 * - $fieldClass lets you specify an EasyAdmin Field class (e.g., TextField::class).
 * - $templatePath can override rendering for custom Twig templates.
 * - $showOn / $hideOn let you target specific pages (Page::INDEX, etc.).
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
        public bool   $index = false,       // Show on INDEX list
        public ?int   $order = null,        // Sort order in listings/forms (lower = earlier)
        public bool   $filter = false,      // Expose as a filter on INDEX
        public ?bool  $sortable = null,     // Override default sortability

        // Presentation
        public ?string $label = null,       // If null, derive from property name (humanized)
        public ?string $help = null,        // Optional help text / hint
        public ?string $width = null,       // Optional column width hint (e.g., '120px', '10ch')
        public ?string $format = null,      // Optional value formatter hint (reader decides how to apply)

        // Rendering strategy
        public ?string $fieldClass = null,  // e.g., \EasyCorp\Bundle\EasyAdminBundle\Field\TextField::class
        public ?string $templatePath = null,// Custom Twig template path for this field
        public ?string $type = null,        // Optional hint ('integer','money','color','year','enum', etc.)

        // Page targeting
        public ?array $showOn = null,       // Explicit allowlist of pages
        public ?array $hideOn = null,       // Explicit denylist of pages

        // Filtering config (future-proof)
        public ?string $filterType = null,  // e.g., 'range','choice','boolean'
        public ?array  $filterOptions = null,// Arbitrary options map for the filter
    ) {}
}
