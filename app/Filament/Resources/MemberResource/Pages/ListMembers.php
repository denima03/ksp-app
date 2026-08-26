<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Exports\MembersTemplateExport;
use App\Filament\Resources\MemberResource;
use App\Models\Member;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // Tombol Download Template Excel
            Actions\Action::make('downloadTemplate')
                ->label('Download Template Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn() => Excel::download(new MembersTemplateExport, 'template_import_anggota.xlsx')),

            // Tombol Import Excel
            Actions\Action::make('importExcel')
                ->label('Import Excel')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->form([
                    FileUpload::make('attachment')
                        ->label('Pilih File Excel (.xlsx / .xls)')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = storage_path('app/public/' . $data['attachment']);

                    try {
                        $spreadsheet = IOFactory::load($filePath);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $excelData = $worksheet->toArray();

                        if (count($excelData) > 1) {
                            $rawHeaders = array_shift($excelData);
                            $headers = array_map(fn($h) => strtolower(trim((string) $h)), $rawHeaders);

                            foreach ($excelData as $rowValues) {
                                $row = array_combine($headers, array_pad($rowValues, count($headers), null));

                                if (empty($row['nik']) && empty($row['nama'])) {
                                    continue;
                                }

                                // 1. Buat atau perbarui Akun User (Login)
                                $email = !empty($row['email']) ? $row['email'] : $row['nik'] . '@koperasi.com';
                                $plainPassword = !empty($row['password']) ? (string) $row['password'] : (string) $row['nik'];

                                $user = User::firstOrNew(['email' => $email]);
                                $user->name = $row['nama'] ?? $user->name ?? 'Anggota';
                                if (!$user->exists || !empty($row['password'])) {
                                    $user->password = Hash::make($plainPassword);
                                }
                                $user->save();

                                // 2. Buat atau perbarui Data Anggota sesuai skema DB
                                $member = Member::firstOrNew(['nik' => (string) $row['nik']]);

                                // Auto generate nomor_anggota jika kosong
                                if (empty($member->nomor_anggota)) {
                                    $nextId = (Member::max('id') ?? 0) + 1;
                                    $member->nomor_anggota = !empty($row['nomor_anggota'])
                                        ? (string) $row['nomor_anggota']
                                        : 'ANG-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                                }

                                // Mapping langsung sesuai kolom tabel `members`
                                $member->user_id = $user->id;
                                $member->nama = $row['nama'] ?? $member->nama ?? '-';
                                $member->tempat_lahir = $row['tempat_lahir'] ?? $member->tempat_lahir ?? '-';
                                $member->tanggal_lahir = !empty($row['tanggal_lahir']) ? $row['tanggal_lahir'] : ($member->tanggal_lahir ?? '1990-01-01');
                                $member->alamat = $row['alamat'] ?? $member->alamat ?? '-';
                                $member->no_hp = $row['no_hp'] ?? $row['no_telepon'] ?? $row['telepon'] ?? $member->no_hp ?? '-';
                                $member->jabatan = $row['jabatan'] ?? $member->jabatan ?? 'Anggota';
                                $member->status_kepegawaian = $row['status_kepegawaian'] ?? $member->status_kepegawaian ?? 'Lainnya';
                                $member->gaji = !empty($row['gaji']) ? (float) $row['gaji'] : ($member->gaji ?? 0);
                                $member->tpp = !empty($row['tpp']) ? (float) $row['tpp'] : ($member->tpp ?? 0);
                                $member->sertifikasi = !empty($row['sertifikasi']) ? (float) $row['sertifikasi'] : ($member->sertifikasi ?? 0);
                                $member->tanggal_masuk = !empty($row['tanggal_masuk']) ? $row['tanggal_masuk'] : ($member->tanggal_masuk ?? now()->format('Y-m-d'));
                                $member->status = strtolower($row['status'] ?? $member->status ?? 'aktif');
                                $member->save();

                                // Hubungkan balik ke User jika tabel users memiliki kolom member_id
                                if (Schema::hasColumn('users', 'member_id')) {
                                    $user->member_id = $member->id;
                                    $user->save();
                                }
                            }
                        }

                        Notification::make()
                            ->title('Import Berhasil')
                            ->body('Data Anggota dan Akun Login berhasil dibuat.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Gagal')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
