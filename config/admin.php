<?php

return [
    'path' => trim((string) env('ADMIN_PORTAL_PATH', 'secure-staff-portal'), '/') ?: 'secure-staff-portal',
    'receptionist' => [
        'name' => env('INQUIRY_RECEPTIONIST_NAME', 'Miss Lindy'),
        'email' => env('INQUIRY_RECEPTIONIST_EMAIL', 'lyn.icsa@gmail.com'),
        'whatsapp' => env('INQUIRY_RECEPTIONIST_WHATSAPP', '96597674076'),
    ],
];
