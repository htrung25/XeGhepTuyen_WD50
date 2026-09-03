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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // ESMS.vn — SMS gateway cho thị trường Việt Nam
    'esms' => [
        'api_key' => env('ESMS_API_KEY'),
        'secret_key' => env('ESMS_SECRET_KEY'),
        'brand_name' => env('ESMS_BRAND_NAME', 'XeGhep'),
        'sms_type' => env('ESMS_SMS_TYPE', 4),
        'base_url' => 'https://rest.esms.vn/MainService.svc/json',
    ],

    // Zalo OA
    'zalo' => [
        'oa_id' => env('ZALO_OA_ID'),
        'access_token' => env('ZALO_OA_ACCESS_TOKEN'),
        'refresh_token' => env('ZALO_OA_REFRESH_TOKEN'),
        'app_id' => env('ZALO_APP_ID'),
        'app_secret' => env('ZALO_APP_SECRET'),
        'base_url' => 'https://openapi.zalo.me/v2.0/oa',
    ],

    // MoMo
    // Dùng `?:` chứ KHÔNG dùng tham số default của env(): biến khai báo trong .env
    // nhưng để trống trả về chuỗi rỗng (default không được áp), khiến redirect/ipn
    // rỗng và MoMo từ chối khởi tạo giao dịch.
    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'create_url' => env('MOMO_CREATE_URL') ?: 'https://test-payment.momo.vn/v2/gateway/api/create',
        'redirect_url' => env('MOMO_REDIRECT_URL')
            ?: rtrim((string) (env('FRONTEND_URL') ?: env('APP_URL')), '/').'/payment/momo/return',
        'notify_url' => env('MOMO_NOTIFY_URL')
            ?: rtrim((string) env('APP_URL'), '/').'/api/customer/payments/momo/callback',
    ],

    // VNPay
    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'api_url' => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
        'return_url' => env('VNPAY_RETURN_URL', env('FRONTEND_URL', env('APP_URL')).'/payment/vnpay/return'),
        'notify_url' => env('VNPAY_NOTIFY_URL', env('APP_URL').'/api/customer/payments/vnpay/callback'),
    ],

    // SePay
    'sepay' => [
        'webhook_api_key' => env('SEPAY_WEBHOOK_API_KEY', env('SEPAY_WEBHOOK_TOKEN', 'DATN-WD50')),
        'bank_acc' => env('SEPAY_BANK_ACC', '8845296516'),
        'bank_name' => env('SEPAY_BANK_NAME', 'BIDV'),
        'acc_name' => env('SEPAY_ACC_NAME', 'TRAN HUU TRUNG'),
    ],

    // Google Maps — ETA calculation
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'base_url' => 'https://maps.googleapis.com/maps/api',
    ],

];
