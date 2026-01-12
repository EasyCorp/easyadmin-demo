<?php

namespace App\Enum;

enum SubscriberSource: string
{
    case Homepage = 'homepage';
    case Blog = 'blog';
    case Popup = 'popup';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Homepage => 'subscriber.source.homepage',
            self::Blog => 'subscriber.source.blog',
            self::Popup => 'subscriber.source.popup',
            self::Import => 'subscriber.source.import',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Homepage => 'primary',
            self::Blog => 'info',
            self::Popup => 'warning',
            self::Import => 'secondary',
        };
    }

    public static function choices(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->label(), self::cases()),
            self::cases()
        );
    }

    public static function badges(): array
    {
        $badges = [];
        foreach (self::cases() as $case) {
            $badges[$case->value] = $case->color();
        }

        return $badges;
    }

    public static function filterChoices(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->label(), self::cases()),
            array_map(fn (self $case) => $case->value, self::cases())
        );
    }
}
