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
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'aws' => [
        'COGNITO_KEYS' => env('AWS_COGNITO_KEYS'),
        'COGNITO_TOKEN' => env('AWS_COGNITO_TOKEN'),
        'COGNITO_CLIENT_ID' => env('AWS_COGNITO_CLIENT_ID'),
        'COGNITO_REGION' => env('AWS_COGNITO_REGION'),
        'COGNITO_USER_POOL_ID' => env('AWS_COGNITO_USER_POOL_ID'),
        'COGNITO_PUBLIC_KEY' => <<<EOD
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvleu+AiTPf88464y4vB3
        0rhpjQbwRFfALzIe7DzyRUUCDm6FKzDrrCd78QbtkOIvAOM9De9Oso61IVtrvtAx
        /QiR9ymAYv4cBrPkuc2S15PCGEaXP03xwMlu6W3VMR0rcnzCMPUkeW9WAnI79w8i
        S5nSpV4QXTau7DCF2gHDveWyWZRH3nayjSXOWip+kZYlyDJ7vATJgfEylTNZ2daG
        g0rG24+ce0a6Jx2X0cWTI6arkn9VQS77MebgdfhMX6uv4kL3I8A0BvhEnkp5W77y
        pjYhxfhjZP68QHKXKksuIKJhM//5SIzhbQt2nbPtPRG0aGyL2riKbI8DGYha0zmZ
        CwIDAQAB
        -----END PUBLIC KEY-----
        EOD,
    ],

];
