<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Services\Sales\ConfirmSale;
use App\Services\Tekra\TekraFelService;
use DOMDocument;
use DOMXPath;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Storage;
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
                        $certificador = new TekraFelService();
                        $resp = $certificador->certificarFactura($sale);

                        // 1) Si trae PDF base64 directo
                        $resultRaw = $resp['raw'];
                        $pdfBase64 = $resp['pdf_base64'] ?? '';
                        $resultado = json_decode($resp['resultado']) ?? '';
                        $documento_certificado = $resp['documento_certificado'] ?? '';
                        $pdf_base64 = $resp['pdf_base64'] ?? '';
                        $qrCode = $resp['qr'] ?? '';

                        logger($resultRaw->NumeroAutorizacion);
                        logger($resultado->error);
                        if ($resultado->error == 1) {
                            $messages = $resultado->frases;
                            foreach ($messages as $message) {
                                Notification::make()
                                    ->title($message)
                                    ->warning()
                                    ->send();
                            }
                            return;
                        }

                        if ($pdfBase64) {
                            $pdfPath = "fel/sale-{$sale->id}.pdf";
                            Storage::disk('public')->put($pdfPath, base64_decode(trim($pdfBase64)));
                        }

                        // 2) Parsear DocumentoCertificado para UUID/serie/numero (cuando viene dentro del XML)
                        $uuid = null;
                        $serie = null;
                        $numero = null;
                        $resultado = null;
                        $fechaHoraCertificacion = null;
                        $fechaHoraEmision = null;
                        $nitCertificador = null;
                        $nombreCertificador = null;
                        $estadoDocumento = null;
                        $nombreReceptor = null;



                        // logger(json_encode($resultRaw));
                        if ($resultRaw) {
                            $uuid = $resultRaw->NumeroAutorizacion;
                            $serie = $resultRaw->SerieDocumento;
                            $numero = $resultRaw->NumeroDocumento;
                            $fechaHoraCertificacion = $resultRaw->FechaHoraCertificacion;
                            $nitCertificador = $resultRaw->NITCertificador;
                            $nombreCertificador = $resultRaw->NombreCertificador;
                            $estadoDocumento = $resultRaw->EstadoDocumento;
                            $nombreReceptor = $resultRaw->NombreReceptor ?? $sale->customer->tax_name;
                            $fechaHoraEmision = $resultRaw->FechaHoraEmision;
                        }


                        logger($uuid);

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
                            'fel_fecha_hora_certificacion' => str_replace('-06:00', '', str_replace('T', ' ', $fechaHoraCertificacion)),
                            'fel_nit_certificador' => $nitCertificador,
                            'fel_nombre_certificador' => $nombreCertificador,
                            'fel_estado_documento' => $estadoDocumento,
                            'fel_nombre_receptor' => $nombreReceptor,
                            'fel_fecha_hora_emision' => str_replace('-06:00', '', str_replace('T', ' ', $fechaHoraEmision)),
                            'fel_status' => 'certified',
                            'status' => 'certified',
                            'fel_qr' => $qrCode
                        ]);


                        Notification::make()
                            ->title('Documento certificado')
                            ->body("UUID: {$uuid}")
                            ->success()
                            ->send();
                        $this->record = $this->record->fresh();
                        $this->refreshFormData(['status', 'fel_uuid', 'fel_serie', 'fel_numero', 'fel_fecha_hora_certificacion', 'fel_nit_certificador', 'fel_nombre_certificador', 'fel_estado_documento', 'fel_nombre_receptor', 'fel_fecha_hora_emision', 'fel_status']);

                    } catch (Throwable $e) {
                        $this->record->update(['fel_status' => 'error']);
                        logger($e->getMessage());
                        Notification::make()
                            ->title('Error al certificar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('imprimir_factura')
                ->label('Imprimir factura')
                ->color('primary')
                ->visible(fn() => $this->record->status === 'certified' && !empty($this->record->fel_uuid))
                ->action(function () {
                    $sale = $this->record;
                    $html = view('pdf.factura2', compact('sale'))->render();

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
                    $pdfBinary = $mpdf->Output("factura_{$sale->fel_serie}_{$sale->fel_numero}.pdf", Destination::STRING_RETURN);

                    // 4) Guardar en storage/public (para poder abrirlo)
                    $relativePath = "fel/factura_{$sale->fel_serie}_{$sale->fel_numero}.pdf";
                    Storage::disk('public')->put($relativePath, $pdfBinary);

                    // 5) Notificar y abrir URL
                    Notification::make()
                        ->title('Factura generada')
                        ->success()
                        ->send();

                    $this->redirect(Storage::disk('public')->url($relativePath));
                }),

            Action::make('anular_factura')
                ->label('Anular factura')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('motivo')
                        ->label('Motivo de anulación')
                        ->required()
                        ->maxLength(255),
                ])

                ->visible(fn() => $this->record->status === 'certified' && !empty($this->record->fel_uuid))
                ->action(function ($data) {
                    try {
                        $sale = $this->record->fresh(['customer']);

                        $certificador = new TekraFelService();
                        $resp = $certificador->anularFactura($sale, $data['motivo']);

                        $raw = $resp['raw'] ?? null;
                        $resultadoStr = (string) ($resp['resultado'] ?? '');
                        $anulacionXmlEscapado = (string) ($resp['documento_certificado'] ?? '');
                        $pdfBase64 = trim((string) ($resp['pdf_base64'] ?? ''));

                        // Evita: Object of class stdClass...
                        if ($raw) {
                            logger('ANULACION RAW', (array) $raw);
                        }

                        // 1) Si ResultadoAnulacion es JSON (como certificación)
                        $resultadoJson = json_decode($resultadoStr);

                        if ($resultadoJson && ($resultadoJson->error ?? null) == 1) {
                            foreach (($resultadoJson->frases ?? []) as $msg) {
                                Notification::make()
                                    ->title((string) $msg)
                                    ->warning()
                                    ->send();
                            }
                            return;
                        }

                        // 2) Si no hay JSON, intentamos validar por XML “AnulacionCertificada”
                        // viene escapado (&lt; &gt;), primero lo decodificamos
                        $anulacionXml = '';
                        if ($anulacionXmlEscapado !== '') {
                            $anulacionXml = html_entity_decode($anulacionXmlEscapado, ENT_QUOTES | ENT_XML1, 'UTF-8');

                            // opcional: guardarlo para auditoría
                            // Storage::disk('public')->put("fel/anulacion-sale-{$sale->id}.xml", $anulacionXml);
                        }

                        // 3) Guardar PDF si vino
                        if ($pdfBase64 !== '') {
                            $pdfPath = "fel/anulacion-sale-{$sale->id}.pdf";
                            Storage::disk('public')->put($pdfPath, base64_decode($pdfBase64));
                        }

                        // 4) Actualizar estados (ajusta nombres de columnas a tu tabla)
                        $sale->update([
                            'status' => 'cancelled',
                            'fel_status' => 'void',
                            // si tienes columnas:
                            'fel_fecha_hora_anulacion' => now(),
                            'fel_motivo_anulacion' => $data['motivo'],
                        ]);

                        //TODO:DEVOLVER STOCK, SEGUN TIPO DE PRODUCTO (ARMAS CON NUMERO DE SERIE, MUNICIONES POR CANTIDAD Y TIPO CAJAS O UNIDADES, ACCESORIOS POR CANTIDAD)
        

                        Notification::make()
                            ->title('Factura anulada correctamente')
                            ->success()
                            ->send();

                        $this->record = $this->record->fresh();
                        $this->refreshFormData(['status', 'fel_status']);

                    } catch (Throwable $e) {
                        $this->record->update(['fel_status' => 'error']);
                        logger($e->getMessage());

                        Notification::make()
                            ->title('Error al anular')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('Imprimir anulación')
                ->label('Imprimir anulación')
                ->color('info')
                ->icon('heroicon-o-printer')
                ->visible(fn() => $this->record->status === 'cancelled' && !empty($this->record->fel_uuid))
                ->action(function () {
                    $sale = $this->record;
                    $html = view('pdf.factura2', compact('sale'))->render();

                    $mpdf = new Mpdf([
                        'format' => 'Letter',
                        'margin_left' => 10,
                        'margin_right' => 10,
                        'margin_top' => 10,
                        'margin_bottom' => 10,
                        'default_font' => 'dejavusans',
                        'default_font_size' => 9,
                    ]);

                    $mpdf->SetWatermarkText('ANULADO');
                    $mpdf->showWatermarkText = true;

                    $mpdf->WriteHTML($html);
                    $pdfBinary = $mpdf->Output("factura_{$sale->fel_serie}_{$sale->fel_numero}.pdf", Destination::STRING_RETURN);

                    // 4) Guardar en storage/public (para poder abrirlo)
                    $relativePath = "fel/factura_anulada_{$sale->fel_serie}_{$sale->fel_numero}.pdf";
                    Storage::disk('public')->put($relativePath, $pdfBinary);

                    Notification::make()
                        ->title('Factura anulada correctamente')
                        ->success()
                        ->send();

                    $this->record = $this->record->fresh();
                    $this->refreshFormData(['status', 'fel_status']);
                    $url = Storage::disk('public')->url($relativePath);
                    return $this->redirect($url);
                }),
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
