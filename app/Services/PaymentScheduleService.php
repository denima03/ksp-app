<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Carbon\Carbon;

class PaymentScheduleService
{
    public function createSchedulesForLoan(Loan $loan): void
    {
        // 1. Hapus jadwal lama milik pinjaman ini (agar selalu bersih & baru)
        LoanSchedule::where('loan_id', $loan->id)->delete();

        $tenor = (int) ($loan->tenor ?? 12);
        $jumlahPinjaman = (float) ($loan->jumlah_pinjaman ?? 0);
        $bungaPersen = (float) ($loan->bunga ?? 0);
        $tipeBunga = strtolower((string) ($loan->tipe_bunga ?? 'flat'));
        $tanggalPinjaman = $loan->tanggal_pinjaman ? Carbon::parse($loan->tanggal_pinjaman) : now();

        if ($tenor <= 0 || $jumlahPinjaman <= 0) {
            return;
        }

        $pokokPerBulan = $jumlahPinjaman / $tenor;
        $sisaPokokAwal = $jumlahPinjaman;

        // 2. Generate Jadwal Angsuran Baru dari Angsuran Ke-1 sampai Ke-N (Tenor)
        for ($i = 1; $i <= $tenor; $i++) {
            $tanggalJatuhTempo = $tanggalPinjaman->copy()->addMonths($i);

            if (in_array($tipeBunga, ['effective', 'efektif', 'menurun'])) {
                $bungaPerBulan = $sisaPokokAwal * ($bungaPersen / 100);
            } else {
                $bungaPerBulan = $jumlahPinjaman * ($bungaPersen / 100);
            }

            $totalAngsuranPerBulan = $pokokPerBulan + $bungaPerBulan;
            $sisaPokokAkhir = max(0, $sisaPokokAwal - $pokokPerBulan);

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'angsuran_ke' => $i,
                'bulan' => (int) $tanggalJatuhTempo->format('m'),
                'tahun' => (int) $tanggalJatuhTempo->format('Y'),
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'pokok' => $pokokPerBulan,
                'bunga' => $bungaPerBulan,
                'jumlah_angsuran' => $totalAngsuranPerBulan,
                'pokok_terbayar' => 0,
                'bunga_terbayar' => 0,
                'jumlah_terbayar' => 0,
                'sisa_pokok' => $sisaPokokAkhir,
                'status' => 'belum_bayar',
                'tanggal_bayar' => null,
            ]);

            $sisaPokokAwal = $sisaPokokAkhir;
        }
    }
}
