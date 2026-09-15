<?php

namespace App\Filament\Pages;

use App\Models\Coa;
use App\Models\JournalDetail;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class GeneralLedger extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.general-ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Laporan Keuangan';

    protected static ?string $navigationLabel = 'Buku Besar';

    protected static ?string $title = 'Buku Besar (General Ledger)';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Superadmin', 'Akuntan', 'Manajer Keuangan', 'Direktur']) ?? false;
    }

    public ?string $coa_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->coa_id = Coa::orderBy('code')->value('id') ? (string) Coa::orderBy('code')->value('id') : null;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->columns(3)
            ->components([
                Select::make('coa_id')
                    ->label('Akun (CoA)')
                    ->options(Coa::orderBy('code')->get()->mapWithKeys(fn ($coa) => [$coa->id => "{$coa->code} - {$coa->name}"]))
                    ->searchable()
                    ->placeholder('Pilih Akun...')
                    ->live(),
                DatePicker::make('start_date')
                    ->label('Dari Tanggal')
                    ->live(),
                DatePicker::make('end_date')
                    ->label('Sampai Tanggal')
                    ->live(),
            ]);
    }

    public function submit(): void
    {
        // Re-renders the component with current form filters
    }

    public function getLedgerDataProperty(): Collection
    {
        if (! $this->coa_id) {
            return collect();
        }

        return JournalDetail::with('journal')
            ->where('coa_id', $this->coa_id)
            ->when($this->start_date, function ($query) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '>=', $this->start_date));
            })
            ->when($this->end_date, function ($query) {
                $query->whereHas('journal', fn ($q) => $q->whereDate('date', '<=', $this->end_date));
            })
            ->get()
            ->sortBy(fn ($detail) => $detail->journal?->date);
    }

    public function getSelectedCoaProperty(): ?Coa
    {
        return $this->coa_id ? Coa::find($this->coa_id) : null;
    }
}
