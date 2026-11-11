<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum OrderStatus: string implements TranslatableInterface
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'order.status.pending',
            self::Paid => 'order.status.paid',
            self::Shipped => 'order.status.shipped',
            self::Delivered => 'order.status.delivered',
            self::Cancelled => 'order.status.cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'secondary',
        };
    }

    /**
     * The number of steps completed in the progress of the order. It's zero
     * for cancelled orders because they don't advance through the steps.
     */
    public function progressStep(): int
    {
        return match ($this) {
            self::Cancelled => 0,
            self::Pending => 1,
            self::Paid => 2,
            self::Shipped => 3,
            self::Delivered => 4,
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
