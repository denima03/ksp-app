<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case BELUM_BAYAR = 'belum_bayar';
    case SEBAGIAN = 'sebagian';
    case LUNAS = 'lunas';
    case TERLAMBAT = 'terlambat';

    public function getLabel(): string
    {
        return match ($this) {
            self::BELUM_BAYAR => 'Belum Bayar',
            self::SEBAGIAN => 'Sebagian',
            self::LUNAS => 'Lunas',
            self::TERLAMBAT => 'Terlambat',
        };
    }
}
