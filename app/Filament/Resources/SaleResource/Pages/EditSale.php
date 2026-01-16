<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\Sales\ConfirmSale;
use App\Services\Tekra\TekraFelService;
use DOMDocument;
use DOMXPath;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Throwable;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['subtotal'] = (float) ($data['subtotal'] ?? 0);
        $data['tax'] = (float) ($data['tax'] ?? 0);
        $data['total'] = (float) ($data['total'] ?? 0);

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirmar venta')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status === 'draft')
                ->action(function () {
                    try {
                        app(ConfirmSale::class)->handle($this->record, auth()->id());

                        Notification::make()
                            ->title('Venta confirmada')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'subtotal',
                            'tax',
                            'total',
                            'confirmed_at',
                        ]);
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('No se pudo confirmar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),


            Action::make('certify_fel')
                ->label('Certificar FEL')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->status === 'confirmed' && empty($this->record->fel_uuid))
                ->action(function () {
                    try {
                        $sale = $this->record->fresh(['items', 'customer']);
                        $resp = app(TekraFelService::class)->certificarFactura($sale);

                        // 1) Si trae PDF base64 directo
                        $pdfBase64 = $resp['pdf_base64'] ?? '';
                        if ($pdfBase64) {
                            $pdfPath = "fel/sale-{$sale->id}.pdf";
                            \Storage::disk('public')->put($pdfPath, base64_decode($pdfBase64));
                        }

                        // 2) Parsear DocumentoCertificado para UUID/serie/numero (cuando viene dentro del XML)
                        $uuid = null;
                        $serie = null;
                        $numero = null;
                        $resultado = null;

                        $docXml = $resp['documento_certificado'] ?? '';
                        if ($docXml) {
                            $dom = new DOMDocument();
                            $dom->loadXML($docXml);

                            $xpath = new DOMXPath($dom);
                            $xpath->registerNamespace('dte', 'http://www.sat.gob.gt/dte/fel/0.2.0');

                            // <dte:NumeroAutorizacion Serie="XXXX" Numero="YYYY">UUID</dte:NumeroAutorizacion>
                            $nodes = $xpath->query('//dte:NumeroAutorizacion');

                            if ($nodes && $nodes->length) {
                                $resultado = $nodes->item(0)->attributes->getNamedItem('ResultadoCertificacion')->nodeValue;
                                $node = $nodes->item(0);
                                $uuid = trim($node->nodeValue);
                                $serie = $node->attributes?->getNamedItem('Serie')?->nodeValue;
                                $numero = $node->attributes?->getNamedItem('Numero')?->nodeValue;
                            }
                        }

                        if (!$uuid) {
                            // si no logramos extraerlo, marca error para revisar
                            $sale->update(['fel_status' => 'error']);
                            Notification::make()->title('FEL no retornó UUID')->danger()->send();
                            return;
                        }

                        $sale->update([
                            'fel_uuid' => $uuid,
                            'fel_serie' => $serie,
                            'fel_numero' => $numero,
                            'fel_status' => 'certified',
                        ]);

                        Notification::make()
                            ->title('Documento certificado')
                            ->body("UUID: {$uuid}")
                            ->success()
                            ->send();

                        $this->refreshFormData(['fel_uuid', 'fel_serie', 'fel_numero', 'fel_status']);

                    } catch (Throwable $e) {
                        $this->record->update(['fel_status' => 'error']);

                        Notification::make()
                            ->title('Error al certificar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];

    }

    protected function getFormActions(): array
    {
        if ($this->record->status !== 'draft') {
            return []; // sin botones (ni guardar)
        }

        return parent::getFormActions();
    }

    protected function beforeSave(): void
    {
        if ($this->record->status !== 'draft') {
            abort(403, 'No puedes modificar una venta confirmada.');
        }
    }
}
