<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'tekra' => [
        'base_url' => env('TEKRA_BASE_URL', 'https://apiseguimiento.tekra.com.gt'),
        'user' => env('TEKRA_USER'),
        'pass' => env('TEKRA_PASS'),
        'empresa' => env('TEKRA_EMPRESA', 1),
        'cliente' => env('TEKRA_CLIENTE'),
        'contrato' => env('TEKRA_CONTRATO'),
    ],

    'tekra_fel' => [
        'wsdl' => env('TEKRA_FEL_WSDL'),
        'url' => env('TEKRA_FEL_URL'),
        'user' => env('TEKRA_USER'),
        'user2' => env('TEKRA_USER2'),
        'pass' => env('TEKRA_PASS'),
        'pass2' => env('TEKRA_PASS2'),
        'cliente' => env('TEKRA_CLIENTE'),
        'contrato' => env('TEKRA_CONTRATO'),
        'id_origen' => env('TEKRA_ID_ORIGEN', 'Sistema Facturacion'),
        'ip_origen' => env('TEKRA_IP_ORIGEN', '127.0.0.1'),
        'firmar_emisor' => env('TEKRA_FIRMAR_EMISOR', 'SI'),
        'validar_identificador' => env('TEKRA_VALIDAR_IDENTIFICADOR', 'SI'),
        'emisor_nit' => env('TEKRA_EMISOR_NIT', '107346834'),
        'emisor_nombre' => env('TEKRA_EMISOR_NOMBRE', 'TEKRA'),
        'emisor_afiliacion' => env('TEKRA_EMISOR_AFILIACION', 'GEN'),
        'establecimiento' => env('TEKRA_ESTABLECIMIENTO', '1'),
        'emisor_direccion' => env('TEKRA_EMISOR_DIRECCION', '123 Main St'),
    ],
];
