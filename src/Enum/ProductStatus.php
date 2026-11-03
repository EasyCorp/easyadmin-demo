<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ProductStatus: string implements TranslatableInterface
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'product.status.draft',
            self::Published => 'product.status.published',
            self::Archived => 'product.status.archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Published => 'success',
            self::Archived => 'warning',
        };
    }

    public static function badges(): array
    {
        $badges = [];
        foreach (self::cases() as $status) {
            $badges[$status->name] = $status->color();
        }

        return $badges;
    }
}
