<?php

namespace App\Enums;

enum RoleEnum : string
{
    case MEMBER = 'member';
    case ADMIN = 'admin';

    public static function values() {
        return array_column(self::cases(), 'value');
    }
}
