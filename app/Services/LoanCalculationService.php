<?php

namespace App\Services;

use App\Models\Loan;

class LoanCalculationService
{
    /**
     * Hitung Pokok per Bulan (Pokok dibayar rata setiap bulan)
     * Rumus: Jumlah Pinjaman / Tenor
     */
    public function calculateMonthlyPrincipal(float $jumlahPinjaman, int $tenor): float
    {
        if ($tenor <= 0) {
            return 0.0;
        }

        return round($jumlahPinjaman / $tenor, 2);
    }

    /**
     * Hitung Bunga Bulan Berjalan (Metode Bunga Menurun)
     * Rumus: Sisa Pokok Awal Bulan * Rate Bunga (misal 2%)
     */
    public function calculateDecliningInterest(float $sisaPokokAwal, float $rateBunga = 2.0): float
    {
        return round($sisaPokokAwal * ($rateBunga / 100), 2);
    }

    /**
     * Hitung Total Bunga selama seluruh Tenor
     */
    public function calculateTotalInterest(float $jumlahPinjaman, int $tenor, float $rateBunga = 2.0): float
    {
        $cicilanPokok = $this->calculateMonthlyPrincipal($jumlahPinjaman, $tenor);
        $sisaPokok = $jumlahPinjaman;
        $totalBunga = 0.0;

        for ($i = 1; $i <= $tenor; $i++) {
            $bungaBulanIni = $this->calculateDecliningInterest($sisaPokok, $rateBunga);
            $totalBunga += $bungaBulanIni;
            $sisaPokok -= $cicilanPokok;
        }

        return round($totalBunga, 2);
    }

    /**
     * Generate Simulasi Jadwal Angsuran
     */
    public function generateSchedulePreview(float $jumlahPinjaman, int $tenor, float $rateBunga = 2.0): array
    {
        $cicilanPokok = $this->calculateMonthlyPrincipal($jumlahPinjaman, $tenor);
        $sisaPokok = $jumlahPinjaman;
        $schedules = [];

        for ($i = 1; $i <= $tenor; $i++) {
            $bungaBulanIni = $this->calculateDecliningInterest($sisaPokok, $rateBunga);
            $angsuranBulanIni = $cicilanPokok + $bungaBulanIni;
            $sisaPokokAkhir = max(0, $sisaPokok - $cicilanPokok);

            $schedules[] = [
                'angsuran_ke' => $i,
                'sisa_pokok_awal' => $sisaPokok,
                'pokok' => $cicilanPokok,
                'bunga' => $bungaBulanIni,
                'jumlah_angsuran' => $angsuranBulanIni,
                'sisa_pokok_akhir' => $sisaPokokAkhir,
            ];

            $sisaPokok = $sisaPokokAkhir;
        }

        return $schedules;
    }

    /**
     * Hitung Sisa Pokok Pinjaman berdasarkan jadwal yang belum lunas
     */
    public function calculateRemainingPrincipal(Loan $loan): float
    {
        $pokokTerbayar = $loan->loanSchedules()->sum('pokok_terbayar');
        $sisaPokok = $loan->jumlah_pinjaman - $pokokTerbayar;

        return max(0, round($sisaPokok, 2));
    }

    /**
     * Hitung Total Sisa Hutang (Kewajiban Angsuran yang belum dibayar)
     */
    public function calculateRemainingDebt(Loan $loan): float
    {
        $unpaidSchedules = $loan->loanSchedules()
            ->where('status', '!=', 'lunas')
            ->get();

        $sisaHutang = 0.0;
        foreach ($unpaidSchedules as $schedule) {
            $sisaHutang += ($schedule->jumlah_angsuran - $schedule->jumlah_terbayar);
        }

        return max(0, round($sisaHutang, 2));
    }

    /**
     * Hitung Pelunasan Dini Pinjaman Reguler (Pelunasan sisa pokok)
     */
    public function calculateEarlySettlement(Loan $loan): array
    {
        $sisaPokok = $this->calculateRemainingPrincipal($loan);
        $sisaHutangTotal = $this->calculateRemainingDebt($loan);

        return [
            'sisa_pokok' => $sisaPokok,
            'total_kewajiban_sisa' => $sisaHutangTotal,
            'total_pelunasan' => $sisaPokok, // Pelunasan dini hanya membebankan sisa pokok
        ];
    }

    /**
     * Hitung Perhitungan Top Up Pinjaman
     * Formula: Dana Diterima = Pinjaman Baru - Sisa Hutang/Pokok Pinjaman Lama
     */
    public function calculateTopUp(Loan $oldLoan, float $newLoanAmount): array
    {
        $sisaHutangLama = $this->calculateRemainingPrincipal($oldLoan);
        $pelunasanPinjamanLama = $sisaHutangLama;
        $danaDiterima = $newLoanAmount - $pelunasanPinjamanLama;

        return [
            'pinjaman_lama' => $oldLoan->jumlah_pinjaman,
            'sisa_hutang_lama' => $sisaHutangLama,
            'pinjaman_baru' => $newLoanAmount,
            'pelunasan_pinjaman_lama' => $pelunasanPinjamanLama,
            'dana_diterima_anggota' => $danaDiterima,
            'is_valid' => $danaDiterima > 0,
        ];
    }

    /**
     * Hitung Bunga & Pelunasan Pinjaman Sementara (Flat 2% Sekaligus)
     */
    public function calculateTemporaryLoan(float $jumlahPinjaman, float $rateBunga = 2.0): array
    {
        $jumlahBunga = round($jumlahPinjaman * ($rateBunga / 100), 2);
        $totalPelunasan = $jumlahPinjaman + $jumlahBunga;

        return [
            'jumlah_pinjaman' => $jumlahPinjaman,
            'bunga_rate' => $rateBunga,
            'jumlah_bunga' => $jumlahBunga,
            'total_pelunasan' => $totalPelunasan,
        ];
    }
}
