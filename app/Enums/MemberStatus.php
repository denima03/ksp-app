<?php

namespace App\Enums;

enum MemberStatus: string
{
    case AKTIF = 'aktif';
    case TIDAK_AKTIF = 'tidak_aktif';

    public function getLabel(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::TIDAK_AKTIF => 'Tidak Aktif',
        };
    }
}
