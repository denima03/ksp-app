<?php

namespace App\Enums;

enum PaymentSource: string
{
    case TRANSFER_MANDIRI = 'transfer_mandiri';
    case POTONGAN_GAJI = 'potongan_gaji';
    case TPP = 'tpp';
    case SERTIFIKASI = 'sertifikasi';

    public function getLabel(): string
    {
        return match ($this) {
            self::TRANSFER_MANDIRI => 'Transfer Mandiri',
            self::POTONGAN_GAJI => 'Potongan Gaji',
            self::TPP => 'TPP',
            self::SERTIFIKASI => 'Sertifikasi',
        };
    }
}
