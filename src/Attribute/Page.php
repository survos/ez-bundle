<?php
declare(strict_types=1);

namespace Survos\EzBundle\Attribute;

/**
 * EasyAdmin page names as constants to avoid coupling to EA directly.
 * Map these to \EasyCorp\Bundle\EasyAdminBundle\Config\Crud constants in your reader.
 */
final class Page
{
    public const INDEX  = 'index';
    public const DETAIL = 'detail';
    public const NEW    = 'new';
    public const EDIT   = 'edit';

    /** @return non-empty-string[] */
    public static function all(): array
    {
        return [self::INDEX, self::DETAIL, self::NEW, self::EDIT];
    }
}
