<?php

namespace App\Filament\Pages;

use App\Models\Accessory;
use App\Models\Ammo;
use App\Models\Sale;
use App\Models\Weapon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class Reports extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $title = 'Reportes';
    protected static string $view = 'filament.pages.reports';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'status' => 'certified',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Filtros para reporte de ventas')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->required(),

                        Forms\Components\DatePicker::make('to')
                            ->label('Hasta')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'certified' => 'Certificadas',
                                'confirmed' => 'Confirmadas',
                                'cancelled' => 'Canceladas',
                                'draft' => 'Borrador',
                                'all' => 'Todas',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    public function inventoryPdf()
    {
        $weapons = Weapon::with(['brand', 'brandModel', 'caliber', 'type'])
            ->orderBy('id')
            ->get();

        $ammos = Ammo::with(['brand', 'caliber'])
            ->orderBy('id')
            ->get();

        $accessories = Accessory::with(['brand', 'category'])
            ->orderBy('name')
            ->get();

        $html = view('pdf.reports.inventory', compact(
            'weapons',
            'ammos',
            'accessories'
        ))->render();

        return $this->generatePdf($html, 'reporte_inventario.pdf');
    }

    public function salesPdf()
    {
        $data = $this->form->getState();

        $sales = Sale::with(['customer', 'items', 'payments'])
            ->when($data['status'] !== 'all', fn($query) => $query->where('sales.status', $data['status']))
            ->whereDate('sales.created_at', '>=', $data['from'])
            ->whereDate('sales.created_at', '<=', $data['to'])
            ->orderBy('sales.created_at')
            ->get();

        $html = view('pdf.reports.sales', [
            'sales' => $sales,
            'from' => $data['from'],
            'to' => $data['to'],
            'status' => $data['status'],
        ])->render();

        return $this->generatePdf($html, 'reporte_ventas.pdf');
    }

    private function generatePdf(string $html, string $filename)
    {
        $mpdf = new Mpdf([
            'format' => 'Letter',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'dejavusans',
            'default_font_size' => 9,
        ]);

        $mpdf->WriteHTML($html);

        $pdfBinary = $mpdf->Output($filename, Destination::STRING_RETURN);

        $relativePath = 'reportes/' . now()->format('Ymd_His') . '_' . $filename;

        Storage::disk('public')->put($relativePath, $pdfBinary);

        Notification::make()
            ->title('Reporte generado')
            ->success()
            ->send();

        return redirect(Storage::disk('public')->url($relativePath));
    }
}