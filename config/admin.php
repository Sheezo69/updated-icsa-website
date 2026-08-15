<?php

return [
    'path' => trim((string) env('ADMIN_PORTAL_PATH', 'secure-staff-portal'), '/') ?: 'secure-staff-portal',
];
