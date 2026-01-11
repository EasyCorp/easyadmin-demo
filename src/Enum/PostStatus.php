<?php

namespace App\Enum;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Scheduled = 'scheduled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'post.status.draft',
            self::Published => 'post.status.published',
            self::Scheduled => 'post.status.scheduled',
            self::Archived => 'post.status.archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Published => 'success',
            self::Scheduled => 'info',
            self::Archived => 'warning',
        };
    }

    public static function choices(): array
    {
        return array_combine(
            array_map(static fn (self $status): string => $status->label(), self::cases()),
            self::cases()
        );
    }

    public static function filterChoices(): array
    {
        return array_combine(
            array_map(static fn (self $status): string => $status->label(), self::cases()),
            array_map(static fn (self $status): string => $status->value, self::cases())
        );
    }

    public static function badges(): array
    {
        $badges = [];
        foreach (self::cases() as $status) {
            $badges[$status->value] = $status->color();
        }

        return $badges;
    }
}
