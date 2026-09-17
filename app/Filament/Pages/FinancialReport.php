<?php

namespace App\Filament\Pages;

use App\Models\Coa;
use App\Models\JournalDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class FinancialReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.financial-report';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static ?string $title = 'Laporan Keuangan (Financial Report)';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (Role::count() === 0 || $user->hasRole('Superadmin')) {
            return true;
        }

        return $user->hasAnyRole(['Superadmin', 'Akuntan', 'Manajer Keuangan', 'Direktur']);
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(2)
            ->components([
                DatePicker::make('start_date')
                    ->label('Dari Tanggal')
                    ->live(),
                DatePicker::make('end_date')
                    ->label('Sampai Tanggal')
                    ->live(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export to PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    if (! class_exists(Pdf::class)) {
                        Notification::make()
                            ->title('Library DomPDF Belum Terinstal')
                            ->body('Silakan jalankan perintah terminal: composer require barryvdh/laravel-dompdf')
                            ->danger()
                            ->send();

                        return;
                    }

                    $data = $this->getViewData();
                    $pdf = Pdf::loadView('pdf.financial-report', $data)
                        ->setPaper('a4', 'portrait');

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'Laporan-Keuangan-SinarWardana.pdf');
                }),
        ];
    }

    public function submit(): void
    {
        // Re-render component on filter update
    }

    public function getViewData(): array
    {
        $startDate = $this->start_date;
        $endDate = $this->end_date;

        // ==========================================
        // A. LABA RUGI (INCOME STATEMENT)
        // ==========================================
        $revenueAccounts = Coa::where('type', 'revenue')->orderBy('code')->get();
        $revenues = [];
        $totalRevenue = 0;

        foreach ($revenueAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);

            if ($startDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '>=', $startDate));
            }
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }

            $details = $query->get();
            // Normal balance Pendapatan: Kredit - Debit
            $balance = (float) $details->sum('credit') - (float) $details->sum('debit');

            $revenues[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalRevenue += $balance;
        }

        $expenseAccounts = Coa::where('type', 'expense')->orderBy('code')->get();
        $expenses = [];
        $totalExpense = 0;

        foreach ($expenseAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);

            if ($startDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '>=', $startDate));
            }
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }

            $details = $query->get();
            // Normal balance Beban: Debit - Kredit
            $balance = (float) $details->sum('debit') - (float) $details->sum('credit');

            $expenses[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalExpense += $balance;
        }

        $netIncome = $totalRevenue - $totalExpense;

        // ==========================================
        // B. NERACA (BALANCE SHEET)
        // ==========================================
        // 1. Aset (Harta/Kas): Debit - Kredit
        $assetAccounts = Coa::where('type', 'asset')->orderBy('code')->get();
        $assets = [];
        $totalAsset = 0;

        foreach ($assetAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }
            $details = $query->get();
            $balance = (float) $details->sum('debit') - (float) $details->sum('credit');

            $assets[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalAsset += $balance;
        }

        // 2. Liabilitas (Hutang): Kredit - Debit
        $liabilityAccounts = Coa::where('type', 'liability')->orderBy('code')->get();
        $liabilities = [];
        $totalLiability = 0;

        foreach ($liabilityAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }
            $details = $query->get();
            $balance = (float) $details->sum('credit') - (float) $details->sum('debit');

            $liabilities[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalLiability += $balance;
        }

        // 3. Ekuitas (Modal): Kredit - Debit
        $equityAccounts = Coa::where('type', 'equity')->orderBy('code')->get();
        $equities = [];
        $totalEquityWithoutEarnings = 0;

        foreach ($equityAccounts as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }
            $details = $query->get();
            $balance = (float) $details->sum('credit') - (float) $details->sum('debit');

            $equities[] = [
                'code' => $coa->code,
                'name' => $coa->name,
                'balance' => $balance,
            ];
            $totalEquityWithoutEarnings += $balance;
        }

        // 4. Laba Kumulatif sampai dengan endDate (agar Neraca Balance)
        $cumulativeRevenue = 0;
        foreach (Coa::where('type', 'revenue')->get() as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }
            $details = $query->get();
            $cumulativeRevenue += ((float) $details->sum('credit') - (float) $details->sum('debit'));
        }

        $cumulativeExpense = 0;
        foreach (Coa::where('type', 'expense')->get() as $coa) {
            $query = JournalDetail::where('coa_id', $coa->id);
            if ($endDate) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $endDate));
            }
            $details = $query->get();
            $cumulativeExpense += ((float) $details->sum('debit') - (float) $details->sum('credit'));
        }

        $cumulativeNetIncome = $cumulativeRevenue - $cumulativeExpense;
        $totalEquity = $totalEquityWithoutEarnings + $cumulativeNetIncome;
        $totalPasiva = $totalLiability + $totalEquity;
        $isBalanced = abs($totalAsset - $totalPasiva) < 0.01;

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            // Laba Rugi
            'revenues' => $revenues,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netIncome' => $netIncome,
            // Neraca
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equities' => $equities,
            'totalAsset' => $totalAsset,
            'totalLiability' => $totalLiability,
            'totalEquityWithoutEarnings' => $totalEquityWithoutEarnings,
            'cumulativeNetIncome' => $cumulativeNetIncome,
            'totalEquity' => $totalEquity,
            'totalPasiva' => $totalPasiva,
            'isBalanced' => $isBalanced,
        ];
    }
}
