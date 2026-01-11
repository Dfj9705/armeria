<?php

namespace App\Services\Tekra;

use Illuminate\Support\Facades\Http;

class TekraContribuyenteService
{
    public function consultaNit(string $nit): array
    {
        $payload = [
            'autenticacion' => [
                'pn_usuario' => config('services.tekra.user'),
                'pn_clave' => config('services.tekra.pass'),
            ],
            'parametros' => [
                'pn_empresa' => (int) config('services.tekra.empresa', 1),
                'pn_cliente' => (int) config('services.tekra.cliente'),
                'pn_contrato' => (int) config('services.tekra.contrato'),
                'pn_nit' => $nit,
            ],
        ];

        $url = rtrim(config('services.tekra.base_url'), '/')
            . '/certificaciones/contribuyente/contribuyente_consulta';

        return Http::timeout(15)->acceptJson()->post($url, $payload)->json() ?? [];
    }

    public function consultaCui(string $cui): array
    {
        $payload = [
            'autenticacion' => [
                'pn_usuario' => config('services.tekra.user'),
                'pn_clave' => config('services.tekra.pass'),
            ],
            'parametros' => [
                'pn_cui' => $cui,
            ],
        ];

        $url = rtrim(config('services.tekra.base_url'), '/')
            . '/certificaciones/contribuyente/contribuyente_consulta_cui';

        return Http::timeout(15)->acceptJson()->post($url, $payload)->json() ?? [];
    }
}
