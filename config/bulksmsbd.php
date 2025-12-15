<?php

return [
    'api_key' => env('BULKSMSBD_API_KEY'),
    'sender_id' => env('BULKSMSBD_SENDER_ID'),
    'api_url' => env('BULKSMSBD_API_URL', 'https://bulksmsbd.net/api/smsapi'),
];