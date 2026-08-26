<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Models\TemporaryLoan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SisaPinjamanOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '5s';

    protected int | string | array $columnSpan = 'full';

    protected int | string | array $columns = [
        'default' => 1,
        'sm'      => 2,
        'md'      => 3,
        'lg'      => 3,
    ];

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return [
                Stat::make('Status Anggota', 'Belum Terhubung')
                    ->description('Akun belum terhubung dengan data anggota')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger')
                    ->chart([10, 5, 2, 0])
                    ->icon('heroicon-o-user-minus')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-red-500/15 via-rose-500/10 to-red-500/5 border-l-4 border-l-red-500 border-y border-r border-red-200 dark:border-red-800/50 dark:from-red-950/50 dark:to-slate-900 rounded-2xl shadow-md',
                    ]),
            ];
        }

        $stats = [];

        // 1. PINJAMAN REGULER
        $loan = Loan::where('member_id', $member->id)
            ->whereIn('status', ['aktif', 'disetujui', 'berjalan', 'active', 'approved'])
            ->latest()
            ->first();

        if ($loan) {
            $sisaPokok = $this->calculateRemainingLoan($loan, 'loan_id');
            $scheduleInfo = $this->getScheduleDetails('loan_id', $loan->id);
            $totalPlafond = (float) ($loan->jumlah_pinjaman ?? $loan->pokok ?? $loan->amount ?? $sisaPokok);

            if ($sisaPokok > 0 || $scheduleInfo['sisa_angsuran'] > 0) {
                $persenSisa = $totalPlafond > 0 ? round(($sisaPokok / $totalPlafond) * 100) : 100;
                $persenTerbayar = max(0, 100 - $persenSisa);

                $color = $persenSisa <= 25 ? 'success' : ($persenSisa <= 60 ? 'warning' : 'primary');

                $description = $scheduleInfo['total_jadwal'] > 0
                    ? "Sisa Pokok • Terbayar {$persenTerbayar}% ({$scheduleInfo['terbayar']}/{$scheduleInfo['total_jadwal']} bln)"
                    : "Sisa Pokok dari plafon awal Rp " . number_format($totalPlafond, 0, ',', '.');

                $chartData = $this->generateChartData($scheduleInfo['total_jadwal'], $scheduleInfo['terbayar']);

                $stats[] = Stat::make('Sisa Pokok Reguler', 'Rp ' . number_format($sisaPokok, 0, ',', '.'))
                    ->description($description)
                    ->descriptionIcon($persenSisa <= 30 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-trending-down')
                    ->color($color)
                    ->chart($chartData)
                    ->icon('heroicon-o-banknotes')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-blue-500/15 via-indigo-500/10 to-purple-500/5 border-l-4 border-l-indigo-500 border-y border-r border-indigo-200 dark:border-indigo-800/50 dark:from-indigo-950/50 dark:to-slate-900 rounded-2xl shadow-md hover:shadow-indigo-500/20 hover:-translate-y-1 transition-all duration-300',
                    ]);
            }
        }

        // 2. PINJAMAN SEMENTARA
        $tempLoan = TemporaryLoan::where('member_id', $member->id)
            ->whereIn('status', ['aktif', 'disetujui', 'berjalan', 'active', 'approved'])
            ->latest()
            ->first();

        if ($tempLoan) {
            $foreignKey = Schema::hasColumn('loan_payments', 'temporary_loan_id') ? 'temporary_loan_id' : 'loan_id';
            $schedKey = Schema::hasColumn('loan_schedules', 'temporary_loan_id') ? 'temporary_loan_id' : 'loan_id';

            $sisaPokokTemp = $this->calculateRemainingLoan($tempLoan, $foreignKey);
            $scheduleInfoTemp = $this->getScheduleDetails($schedKey, $tempLoan->id);
            $totalPlafondTemp = (float) ($tempLoan->jumlah_pinjaman ?? $tempLoan->pokok ?? $tempLoan->amount ?? $sisaPokokTemp);

            if ($sisaPokokTemp > 0 || $scheduleInfoTemp['sisa_angsuran'] > 0) {
                $persenSisaTemp = $totalPlafondTemp > 0 ? round(($sisaPokokTemp / $totalPlafondTemp) * 100) : 100;
                $persenTerbayarTemp = max(0, 100 - $persenSisaTemp);

                $descriptionTemp = $scheduleInfoTemp['total_jadwal'] > 0
                    ? "Sisa Pokok • Terbayar {$persenTerbayarTemp}% ({$scheduleInfoTemp['terbayar']}/{$scheduleInfoTemp['total_jadwal']} bln)"
                    : "Sisa Pokok dari plafon awal Rp " . number_format($totalPlafondTemp, 0, ',', '.');

                $chartDataTemp = $this->generateChartData($scheduleInfoTemp['total_jadwal'], $scheduleInfoTemp['terbayar']);

                $stats[] = Stat::make('Sisa Pokok Sementara', 'Rp ' . number_format($sisaPokokTemp, 0, ',', '.'))
                    ->description($descriptionTemp)
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning')
                    ->chart($chartDataTemp)
                    ->icon('heroicon-o-bolt')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-amber-500/15 via-orange-500/10 to-yellow-500/5 border-l-4 border-l-amber-500 border-y border-r border-amber-200 dark:border-amber-800/50 dark:from-amber-950/50 dark:to-slate-900 rounded-2xl shadow-md hover:shadow-amber-500/20 hover:-translate-y-1 transition-all duration-300',
                    ]);
            }
        }

        if (empty($stats)) {
            return [
                Stat::make('Status Keuangan', 'Bebas Pinjaman')
                    ->description('Lunas! Seluruh sisa pokok pinjaman Anda telah selesai.')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color('success')
                    ->chart([1, 3, 5, 8, 12, 15, 20])
                    ->icon('heroicon-o-sparkles')
                    ->extraAttributes([
                        'class' => 'bg-gradient-to-br from-emerald-500/15 via-teal-500/10 to-green-500/5 border-l-4 border-l-emerald-500 border-y border-r border-emerald-200 dark:border-emerald-800/50 dark:from-emerald-950/50 dark:to-slate-900 rounded-2xl shadow-md hover:shadow-emerald-500/20 hover:-translate-y-1 transition-all duration-300',
                    ]),
            ];
        }

        return $stats;
    }

    private function calculateRemainingLoan(Model $loanModel, string $foreignKeyField): float
    {
        if (isset($loanModel->sisa_pokok) && $loanModel->sisa_pokok !== null) {
            return (float) $loanModel->sisa_pokok;
        }

        $jumlahPinjaman = $loanModel->jumlah_pinjaman ?? $loanModel->pokok ?? $loanModel->amount ?? 0;

        if (Schema::hasTable('loan_schedules')) {
            $schedQuery = LoanSchedule::where($foreignKeyField, $loanModel->id);

            $pokokCol = Schema::hasColumn('loan_schedules', 'angsuran_pokok')
                ? 'angsuran_pokok'
                : (Schema::hasColumn('loan_schedules', 'pokok')
                    ? 'pokok'
                    : (Schema::hasColumn('loan_schedules', 'nominal_pokok') ? 'nominal_pokok' : null));

            if ($pokokCol) {
                $unpaidPokokSum = (clone $schedQuery)->where(function ($q) {
                    if (Schema::hasColumn('loan_schedules', 'status')) {
                        $q->whereNotIn('status', ['lunas', 'paid', 'success', 'sudah_bayar', '1', 1]);
                    } elseif (Schema::hasColumn('loan_schedules', 'paid_at')) {
                        $q->whereNull('paid_at');
                    }
                })->sum($pokokCol);

                if ($unpaidPokokSum > 0) {
                    return (float) $unpaidPokokSum;
                }
            }
        }

        if (Schema::hasTable('loan_payments')) {
            $payPokokCol = Schema::hasColumn('loan_payments', 'angsuran_pokok')
                ? 'angsuran_pokok'
                : (Schema::hasColumn('loan_payments', 'pokok')
                    ? 'pokok'
                    : (Schema::hasColumn('loan_payments', 'jumlah_pokok') ? 'jumlah_pokok' : null));

            if ($payPokokCol) {
                $totalPokokPaid = LoanPayment::where($foreignKeyField, $loanModel->id)
                    ->where(function ($q) {
                        if (Schema::hasColumn('loan_payments', 'status')) {
                            $q->whereNotIn('status', ['gagal', 'failed', 'batal', 'cancelled', 'rejected']);
                        }
                    })
                    ->sum($payPokokCol);

                return (float) max(0, $jumlahPinjaman - $totalPokokPaid);
            }
        }

        if (isset($loanModel->sisa_pinjaman) && $loanModel->sisa_pinjaman !== null) {
            return (float) $loanModel->sisa_pinjaman;
        }

        return (float) $jumlahPinjaman;
    }

    private function generateChartData(int $totalJadwal, int $terbayar): array
    {
        if ($totalJadwal <= 0) {
            return [10, 8, 6, 4, 2];
        }

        $points = [];
        for ($i = 0; $i <= 5; $i++) {
            $progress = round(($terbayar / $totalJadwal) * ($i / 5) * 100);
            $points[] = max(0, 100 - $progress);
        }

        return array_reverse($points);
    }

    private function getScheduleDetails(string $foreignKeyField, mixed $loanId): array
    {
        if (!Schema::hasTable('loan_schedules')) {
            return ['total_jadwal' => 0, 'sisa_angsuran' => 0, 'terbayar' => 0];
        }

        $totalJadwal = LoanSchedule::where($foreignKeyField, $loanId)->count();

        $sisaAngsuran = LoanSchedule::where($foreignKeyField, $loanId)
            ->where(function ($q) {
                if (Schema::hasColumn('loan_schedules', 'status')) {
                    $q->whereNotIn('status', ['lunas', 'paid', 'success', 'sudah_bayar', '1', 1]);
                } elseif (Schema::hasColumn('loan_schedules', 'paid_at')) {
                    $q->whereNull('paid_at');
                }
            })
            ->count();

        return [
            'total_jadwal' => $totalJadwal,
            'sisa_angsuran' => $sisaAngsuran,
            'terbayar' => max(0, $totalJadwal - $sisaAngsuran),
        ];
    }
}
