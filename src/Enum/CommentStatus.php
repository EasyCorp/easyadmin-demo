<?php

namespace App\Enum;

enum CommentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Spam = 'spam';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'comment.status.pending',
            self::Approved => 'comment.status.approved',
            self::Spam => 'comment.status.spam',
            self::Rejected => 'comment.status.rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Spam => 'danger',
            self::Rejected => 'secondary',
        };
    }

    public static function choices(): array
    {
        return array_combine(
            array_map(static fn (self $case) => $case->label(), self::cases()),
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
            array_map(static fn (self $case) => $case->label(), self::cases()),
            array_map(static fn (self $case) => $case->value, self::cases())
        );
    }
}
