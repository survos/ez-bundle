<?php
declare(strict_types=1);

namespace Survos\EzBundle\Service;

/**
 * Thin container for precomputed EZ admin metadata.
 * Shape:
 * [
 *   \App\Entity\Foo::class => [
 *     'admin'  => ['label'=>..., 'icon'=>..., 'defaultSort'=>..., 'indexMax'=>..., 'hiddenPages'=>..., 'pageSizes'=>...],
 *     'fields' => [
 *        'year' => ['index'=>true,'order'=>1,'filter'=>true,'sortable'=>null,'label'=>null,'help'=>null,'width'=>null,'format'=>null,'fieldClass'=>null,'templatePath'=>null,'type'=>null,'showOn'=>null,'hideOn'=>null,'filterType'=>null,'filterOptions'=>null],
 *        ...
 *     ],
 *   ],
 * ]
 */
final class EzService
{
    /** @var array<string, array<string,mixed>> */
    public function __construct(private readonly array $map = [])
    {
    }

    /** @return array<string, mixed>|null */
    public function get(string $class): ?array
    {
        return $this->map[$class] ?? null;
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return $this->map;
    }

    /** @return array<string, mixed> */
    public function getAdmin(string $class): array
    {
        return $this->map[$class]['admin'] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function getFields(string $class): array
    {
        return $this->map[$class]['fields'] ?? [];
    }

    /**
     * Handy helper for INDEX page: returns ordered, truncated field names.
     * @return string[]
     */
    public function getIndexFieldNames(string $class, int $fallbackMax = 7): array
    {
        $fields = $this->getFields($class);
        $indexMax = $this->getAdmin($class)['indexMax'] ?? $fallbackMax;

        $filtered = array_filter($fields, static fn(array $f) => ($f['index'] ?? false) === true);

        uasort($filtered, static function (array $a, array $b): int {
            return ($a['order'] ?? 1000) <=> ($b['order'] ?? 1000);
        });

        $names = array_keys($filtered);

        if (\count($names) > $indexMax) {
            $names = \array_slice($names, 0, $indexMax);
        }

        return $names;
    }
}
