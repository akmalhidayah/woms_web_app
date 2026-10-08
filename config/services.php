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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
        'history_consumable_sheet' => env('GOOGLE_SHEETS_HISTORY_CONSUMABLE_SHEET'),
        'stock_consumable_sheet' => env('GOOGLE_SHEETS_STOCK_CONSUMABLE_SHEET'),
        'stock_consumable_gudang_sheet' => env('GOOGLE_SHEETS_STOCK_CONSUMABLE_GUDANG_SHEET', 'STOCK CONS GUDANG'),
        'stock_material_bms_sheet' => env('GOOGLE_SHEETS_STOCK_MATERIAL_BMS_SHEET', 'STOCK MATERIAL BMS'),
        'stock_material_gudang_sheet' => env('GOOGLE_SHEETS_STOCK_MATERIAL_GUDANG_SHEET', 'STOK MATERIAL GUDANG'),
        'daily_report_sheet' => env('GOOGLE_SHEETS_DAILY_REPORT_SHEET', 'Input LapHarian'),
        'data_sheet' => env('GOOGLE_SHEETS_DATA_SHEET', 'Data'),
        'drive_data_images_folder_id' => env('GOOGLE_DRIVE_DATA_IMAGES_FOLDER_ID'),
        'drive_stock_consumable_images_folder_id' => env('GOOGLE_DRIVE_STOCK_CONSUMABLE_IMAGES_FOLDER_ID'),
        'drive_daily_report_images_folder_id' => env('GOOGLE_DRIVE_DAILY_REPORT_IMAGES_FOLDER_ID'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
