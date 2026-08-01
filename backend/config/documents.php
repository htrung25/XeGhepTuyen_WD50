<?php

return [
    // Local dùng storage/app/private; production nên đặt `s3` hoặc object storage tương thích S3.
    // Để trống PRIVATE_DOCUMENT_DISK sẽ dùng bucket/disk mặc định mà Laravel Cloud inject.
    'disk' => env('PRIVATE_DOCUMENT_DISK', env('FILESYSTEM_DISK', 'local')),
    'url_ttl_minutes' => (int) env('PRIVATE_DOCUMENT_URL_TTL', 5),
    'allow_local_in_production' => (bool) env('PRIVATE_DOCUMENT_ALLOW_LOCAL_IN_PRODUCTION', false),
];
