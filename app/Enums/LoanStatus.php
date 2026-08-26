<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PENGAJUAN = 'pengajuan';
    case DISETUJUI = 'disetujui';
    case AKTIF = 'aktif';
    case LUNAS = 'lunas';
    case DITOLAK = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENGAJUAN => 'Pengajuan',
            self::DISETUJUI => 'Disetujui',
            self::AKTIF => 'Aktif',
            self::LUNAS => 'Lunas',
            self::DITOLAK => 'Ditolak',
        };
    }
}
