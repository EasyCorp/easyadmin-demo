<?php

namespace App\Enum;

enum DemoUser: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Reader = 'reader';

    public function email(): string
    {
        return $this->value.'@example.com';
    }

    public function fullName(): string
    {
        return match ($this) {
            self::Admin => 'Elena Navarro',
            self::Editor => 'Thomas Weber',
            self::Reader => 'Colette Moreau',
        };
    }

    public function role(): string
    {
        return match ($this) {
            self::Admin => 'ROLE_ADMIN',
            self::Editor => 'ROLE_EDITOR',
            self::Reader => 'ROLE_USER',
        };
    }
}
