<?php

namespace App\Enums;

enum TemporaryLoanStatus: string
{
    case AKTIF = 'aktif';
    case LUNAS = 'lunas';

    public function getLabel(): string
    {
        return match ($this) {
            self::AKTIF => 'Aktif',
            self::LUNAS => 'Lunas',
        };
    }
}
