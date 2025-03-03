<?php

namespace App\Enums;

enum StatusEnum : string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    public static function values() {
        return array_column(self::cases(), 'value');
    }

}
