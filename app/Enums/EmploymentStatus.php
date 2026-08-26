<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case PNS = 'PNS';
    case PPPK = 'PPPK';
    case PPPK_PW = 'PPPK PW';
    case LAINNYA = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::PNS => 'PNS',
            self::PPPK => 'PPPK',
            self::PPPK_PW => 'PPPK PW',
            self::LAINNYA => 'Lainnya',
        };
    }
}
