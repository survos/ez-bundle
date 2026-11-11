<?php

declare(strict_types=1);

namespace Survos\EzBundle\Translation;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** Resolves field keys at rendering time, with a readable missing-key fallback. */
final readonly class FieldLabel implements TranslatableInterface
{
    public function __construct(
        public string $key,
        public string $fallback,
        public string $domain = 'fields',
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        if ($translator instanceof TranslatorBagInterface && !$translator->getCatalogue($locale)->has($this->key, $this->domain)) {
            return $this->fallback;
        }

        $translated = $translator->trans($this->key, [], $this->domain, $locale);

        return !$translator instanceof TranslatorBagInterface && $translated === $this->key ? $this->fallback : $translated;
    }
}
