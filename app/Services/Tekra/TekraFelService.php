<?php

namespace App\Services\Tekra;

use App\Models\Sale;
use Illuminate\Support\Str;
use SoapClient;
use SoapFault;
use SoapVar;

class TekraFelService
{
  public function certificarFactura(Sale $sale): array
  {
    $xml = $this->buildFacturaXml($sale);
    logger($xml);

    $wsdl = config('services.tekra_fel.wsdl');

    $client = new SoapClient($wsdl, [
      'trace' => true,
      'exceptions' => true,
      'cache_wsdl' => WSDL_CACHE_NONE,
    ]);

    // Nodo Autenticacion (según manual)
    // pn_validar_identificador: SI/NO valida campo Adenda.DECertificador 
    $auth = [
      'pn_usuario' => config('services.tekra_fel.user2'),
      'pn_clave' => config('services.tekra_fel.pass2'),
      'pn_cliente' => config('services.tekra_fel.cliente'),
      'pn_contrato' => config('services.tekra_fel.contrato'),
      'pn_id_origen' => config('services.tekra_fel.id_origen'),
      'pn_ip_origen' => config('services.tekra_fel.ip_origen'),
      'pn_firmar_emisor' => config('services.tekra_fel.firmar_emisor', 'SI'),
      'pn_validar_identificador' => config('services.tekra_fel.validar_identificador', 'SI'),
    ];

    // $auth = [
    //   'pn_usuario' => 'tekra_api',
    //   'pn_clave' => '123456789',
    //   'pn_cliente' => '2121010001',
    //   'pn_contrato' => '2122010001',
    //   'pn_id_origen' => 'Armeria',
    //   'pn_ip_origen' => '127.0.0.1',
    //   'pn_firmar_emisor' => 'SI',
    //   'pn_validar_identificador' => 'SI',
    // ];
    $xmlLimpio = preg_replace('/^<!\[CDATA\[(.*)\]\]>$/s', '$1', trim($xml));

    // (opcional) quitar el header xml si el servicio no lo quiere dentro:
    $xmlLimpio = preg_replace('/^\s*<\?xml[^>]+\?>\s*/', '', $xmlLimpio);

    $documentoNodo = '<Documento><![CDATA[' . $xmlLimpio . ']]></Documento>';
    $documento = new SoapVar(
      $documentoNodo,
      XSD_ANYXML,
      null,
      null,
      'Documento'
    );

    try {
      // El método recibe 2 nodos: Autenticacion y Documento(CDATA) 
      $resp = $client->__soapCall('CertificacionDocumento', [
        [
          'Autenticacion' => $auth,
          'Documento' => $documento,
        ]
      ]);
      // TEKRA suele devolver strings dentro de nodos (ResultadoCertificacion JSON y otros)
      // Ejemplo/valores retorno: UUID, serie, numero, pdf base64 
      return [
        'raw' => $resp,
        'resultado' => (string) ($resp->ResultadoCertificacion ?? ''),
        'documento_certificado' => (string) ($resp->DocumentoCertificado ?? ''),
        'pdf_base64' => (string) ($resp->RepresentacionGrafica ?? ''),
        'qr' => (string) ($resp->CodigoQR ?? ''),
      ];
    } catch (SoapFault $sf) {
      logger($sf);
      logger($client->__getLastRequest());
      logger($client->__getLastResponse());
      return [
        'raw' => $resp,
        'resultado' => (string) ($resp->ResultadoCertificacion ?? ''),
        'documento_certificado' => (string) ($resp->DocumentoCertificado ?? ''),
        'pdf_base64' => (string) ($resp->RepresentacionGrafica ?? ''),
        'qr' => (string) ($resp->CodigoQR ?? ''),
      ];
    }
  }

  private function buildFacturaXml(Sale $sale): string
  {
    $sale->load('customer', 'items');

    // ⚠️ Ajusta estos datos del emisor a tu config/tabla (por ahora hardcode/config)
    $emisorNit = config('fel.emisor_nit', '107346834');
    $emisorNombre = config('fel.emisor_nombre', 'TEKRA');
    $emisorAfiliacion = config('fel.emisor_iva', 'GEN');
    $establecimiento = config('fel.emisor_establecimiento', '1');

    $receptorId = $sale->customer?->nit ?: ($sale->customer?->cui ?: 'CF');
    $receptorNombre = $sale->customer?->tax_name ?: ($sale->customer?->name ?: 'CONSUMIDOR FINAL');
    $correo = $sale->customer?->email ?: '';

    $numeroAcceso = random_int(100000000, 999999999); // 9 dígitos (SAT)

    $fecha = now()->format('Y-m-d\TH:i:sP'); // -06:00 incluido

    $itemsXml = '';
    $totalImpuestoIva = 0.0;
    $granTotal = 0.0;

    foreach ($sale->items as $idx => $item) {
      $linea = $idx + 1;
      $cantidad = (float) $item->qty;
      $precioUnit = round((float) $item->unit_price, 2);
      $descuento = round((float) $item->discount, 2);
      $precio = round($cantidad * $precioUnit, 2);
      $precioFinal = round($precio - $descuento, 2);

      // Asumimos precio incluye IVA (12%); para FEL se reporta MontoGravable + MontoImpuesto.
      // Ejemplo del manual: MontoGravable y MontoImpuesto por item 
      $montoGravable = round($precioFinal / 1.12, 5);
      $montoImpuesto = round($precioFinal - $montoGravable, 5);

      $totalImpuestoIva += $montoImpuesto;
      $granTotal += $precioFinal;

      $desc = htmlspecialchars($item->description_snapshot ?? 'Item', ENT_XML1);

      $itemsXml .= <<<XML
<dte:Item NumeroLinea="{$linea}" BienOServicio="B">
  <dte:Cantidad>{$cantidad}</dte:Cantidad>
  <dte:Descripcion>{$desc}</dte:Descripcion>
  <dte:PrecioUnitario>{$precioUnit}</dte:PrecioUnitario>
  <dte:Precio>{$precio}</dte:Precio>
  <dte:Descuento>{$descuento}</dte:Descuento>
  <dte:Impuestos>
    <dte:Impuesto>
      <dte:NombreCorto>IVA</dte:NombreCorto>
      <dte:CodigoUnidadGravable>1</dte:CodigoUnidadGravable>
      <dte:MontoGravable>{$montoGravable}</dte:MontoGravable>
      <dte:MontoImpuesto>{$montoImpuesto}</dte:MontoImpuesto>
    </dte:Impuesto>
  </dte:Impuestos>
  <dte:Total>{$precioFinal}</dte:Total>
</dte:Item>
XML;
    }

    $totalImpuestoIva = round($totalImpuestoIva, 5);
    $granTotal = round($granTotal, 5);

    // DECertificador: identificador único para evitar doble certificación 
    $deCertificador = 'SALE-ARMERIA-' . $sale->id;

    // Nota: el XML debe ir en CDATA y cuidar caracteres (& debe ir como &amp;) 
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<dte:GTDocumento Version="0.1"
  xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0"
  xmlns:cfc="http://www.sat.gob.gt/dte/fel/CompCambiaria/0.1.0"
  xmlns:cex="http://www.sat.gob.gt/face2/ComplementoExportaciones/0.1.0"
  xmlns:cfe="http://www.sat.gob.gt/face2/ComplementoFacturaEspecial/0.1.0"
  xmlns:cno="http://www.sat.gob.gt/face2/ComplementoReferenciaNota/0.1.0"
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dte:SAT ClaseDocumento="dte">
    <dte:DTE ID="DatosCertificados">
      <dte:DatosEmision ID="DatosEmision">
        <dte:DatosGenerales Tipo="FACT" FechaHoraEmision="{$fecha}" CodigoMoneda="GTQ" NumeroAcceso="{$numeroAcceso}" />
        <dte:Emisor NITEmisor="{$emisorNit}" NombreEmisor="{$emisorNombre}" CodigoEstablecimiento="{$establecimiento}" NombreComercial="{$emisorNombre}" CorreoEmisor="" AfiliacionIVA="{$emisorAfiliacion}">
          <dte:DireccionEmisor>
            <dte:Direccion>Guatemala</dte:Direccion>
            <dte:CodigoPostal>01010</dte:CodigoPostal>
            <dte:Municipio>Guatemala</dte:Municipio>
            <dte:Departamento>GUATEMALA</dte:Departamento>
            <dte:Pais>GT</dte:Pais>
          </dte:DireccionEmisor>
        </dte:Emisor>
        <dte:Receptor IDReceptor="{$receptorId}" NombreReceptor="{$receptorNombre}" CorreoReceptor="{$correo}">
          <dte:DireccionReceptor>
            <dte:Direccion>Guatemala</dte:Direccion>
            <dte:CodigoPostal>0</dte:CodigoPostal>
            <dte:Municipio></dte:Municipio>
            <dte:Departamento></dte:Departamento>
            <dte:Pais>GT</dte:Pais>
          </dte:DireccionReceptor>
        </dte:Receptor>
        <dte:Frases>
          <dte:Frase TipoFrase="1" CodigoEscenario="1"/>
        </dte:Frases>
        <dte:Items>
          {$itemsXml}
        </dte:Items>

        <dte:Totales>
          <dte:TotalImpuestos>
            <dte:TotalImpuesto NombreCorto="IVA" TotalMontoImpuesto="{$totalImpuestoIva}" />
          </dte:TotalImpuestos>
          <dte:GranTotal>{$granTotal}</dte:GranTotal>
        </dte:Totales>
      </dte:DatosEmision>
    </dte:DTE>

    <dte:Adenda>
      <DECertificador>{$deCertificador}</DECertificador>
    </dte:Adenda>
  </dte:SAT>
</dte:GTDocumento>
XML;
  }

  public function anularFactura(Sale $sale, string $motivo): array
  {
    $xml = $this->buildAnulacionXML($sale, $motivo);

    $wsdl = config('services.tekra_fel.wsdl');

    $client = new SoapClient($wsdl, [
      'trace' => true,
      'exceptions' => true,
      'cache_wsdl' => WSDL_CACHE_NONE,
    ]);

    // Nodo Autenticacion (según manual)
    // pn_validar_identificador: SI/NO valida campo Adenda.DECertificador 
    $auth = [
      'pn_usuario' => config('services.tekra_fel.user2'),
      'pn_clave' => config('services.tekra_fel.pass2'),
      'pn_cliente' => config('services.tekra_fel.cliente'),
      'pn_contrato' => config('services.tekra_fel.contrato'),
      'pn_id_origen' => config('services.tekra_fel.id_origen'),
      'pn_ip_origen' => config('services.tekra_fel.ip_origen'),
      'pn_firmar_emisor' => config('services.tekra_fel.firmar_emisor', 'SI'),
    ];

    // $auth = [
    //   'pn_usuario' => 'tekra_api',
    //   'pn_clave' => '123456789',
    //   'pn_cliente' => '2121010001',
    //   'pn_contrato' => '2122010001',
    //   'pn_id_origen' => 'Armeria',
    //   'pn_ip_origen' => '127.0.0.1',
    //   'pn_firmar_emisor' => 'SI',
    //   'pn_validar_identificador' => 'SI',
    // ];
    $xmlLimpio = preg_replace('/^<!\[CDATA\[(.*)\]\]>$/s', '$1', trim($xml));

    $documentoNodo = '<Documento><![CDATA[' . $xmlLimpio . ']]></Documento>';
    $documento = new SoapVar(
      $documentoNodo,
      XSD_ANYXML,
      null,
      null,
      'Documento'
    );

    try {
      // El método recibe 2 nodos: Autenticacion y Documento(CDATA) 
      $resp = $client->__soapCall('AnulacionDocumento', [
        [
          'Autenticacion' => $auth,
          'Documento' => $documento,
        ]
      ]);
      logger($client->__getLastRequest());
      logger($client->__getLastResponse());
      // TEKRA suele devolver strings dentro de nodos (ResultadoCertificacion JSON y otros)
      // Ejemplo/valores retorno: UUID, serie, numero, pdf base64 
      return [
        'raw' => $resp,
        'resultado' => (string) ($resp->ResultadoAnulacion ?? ''),
        'documento_certificado' => (string) ($resp->AnulacionCertificada ?? ''),
        'pdf_base64' => (string) ($resp->RepresentacionGrafica ?? ''),
      ];
    } catch (SoapFault $sf) {
      logger($sf);
      logger($client->__getLastRequest());
      logger($client->__getLastResponse());
      return [
        'raw' => $resp,
        'resultado' => (string) ($resp->ResultadoAnulacion ?? ''),
        'documento_certificado' => (string) ($resp->AnulacionCertificada ?? ''),
        'pdf_base64' => (string) ($resp->RepresentacionGrafica ?? ''),
      ];
    }
  }

  private function buildAnulacionXML(Sale $sale, $motivo): string
  {
    $sale->load('customer');

    logger($sale->customer->nit);

    // ⚠️ Ajusta estos datos del emisor a tu config/tabla (por ahora hardcode/config)
    $emisorNit = config('fel.emisor_nit', '107346834');
    $uuid = $sale->fel_uuid;
    // str_replace('-06:00', '', str_replace('T', ' ', $fechaHoraEmision)),
    $fechaHoraCertificacion = str_replace(' ', 'T', $sale->fel_fecha_hora_emision) . '-06:00';
    $nitReceptor = $sale->customer->nit;


    $fecha = now()->format('Y-m-d\TH:i:sP'); // -06:00 incluido

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<dte:GTAnulacionDocumento Version="0.1"
  xmlns:dte="http://www.sat.gob.gt/dte/fel/0.1.0"
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dte:SAT>
    <dte:AnulacionDTE ID="DatosCertificados">
      <dte:DatosGenerales ID="DatosAnulacion" NumeroDocumentoAAnular="{$uuid}" NITEmisor="{$emisorNit}" IDReceptor="{$nitReceptor}" FechaEmisionDocumentoAnular="{$fechaHoraCertificacion}" FechaHoraAnulacion="{$fecha}" MotivoAnulacion="{$motivo}"/>
    </dte:AnulacionDTE>
  </dte:SAT>
</dte:GTAnulacionDocumento>
XML;
  }
}
