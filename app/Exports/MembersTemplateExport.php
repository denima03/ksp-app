<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MembersTemplateExport implements FromCollection, WithHeadings
{
    public function headings(): array
    {
        return [
            'nik',
            'nomor_anggota',
            'nama',
            'tempat_lahir',
            'tanggal_lahir',
            'alamat',
            'no_hp',
            'jabatan',
            'status_kepegawaian',
            'gaji',
            'tpp',
            'sertifikasi',
            'tanggal_masuk',
            'status',
            'email',
            'password',
        ];
    }

    public function collection(): Collection
    {
        return collect([
            [
                'nik' => '3278044905820016',
                'nomor_anggota' => 'ANG-0001',
                'nama' => 'Suminar',
                'tempat_lahir' => 'Tasikmalaya',
                'tanggal_lahir' => '1990-01-01',
                'alamat' => 'Tajur Indah Kel. Panyingkiran Kec.Indihiang Kota Tasikmalaya',
                'no_hp' => '081234567890',
                'jabatan' => 'Anggota',
                'status_kepegawaian' => 'PNS',
                'gaji' => 3000000,
                'tpp' => 1000000,
                'sertifikasi' => 0,
                'tanggal_masuk' => '2026-01-01',
                'status' => 'aktif',
                'email' => 'suminar@gmail.com',
                'password' => '12345678',
            ],
        ]);
    }
}
