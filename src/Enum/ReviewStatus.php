<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ReviewStatus: string implements TranslatableInterface
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Spam = 'spam';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'review.status.pending',
            self::Approved => 'review.status.approved',
            self::Rejected => 'review.status.rejected',
            self::Spam => 'review.status.spam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'secondary',
            self::Spam => 'danger',
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
