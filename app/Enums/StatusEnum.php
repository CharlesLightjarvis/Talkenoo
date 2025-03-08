<?php

namespace App\Enums;

enum StatusEnum: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case AWAY = 'away';
    case BUSY  = 'busy';

    public static function values()
    {
        return array_column(self::cases(), 'value');
    }
}
