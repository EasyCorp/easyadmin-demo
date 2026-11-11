<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CustomerSource: string implements TranslatableInterface
{
    case Website = 'website';
    case Newsletter = 'newsletter';
    case Store = 'store';
    case Referral = 'referral';
    case Import = 'import';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }

    public function label(): string
    {
        return match ($this) {
            self::Website => 'customer.source.website',
            self::Newsletter => 'customer.source.newsletter',
            self::Store => 'customer.source.store',
            self::Referral => 'customer.source.referral',
            self::Import => 'customer.source.import',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Website => 'primary',
            self::Newsletter => 'info',
            self::Store => 'success',
            self::Referral => 'warning',
            self::Import => 'secondary',
        };
    }

    public static function badges(): array
    {
        $badges = [];
        foreach (self::cases() as $source) {
            $badges[$source->name] = $source->color();
        }

        return $badges;
    }
}
