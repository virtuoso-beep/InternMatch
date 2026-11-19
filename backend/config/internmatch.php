<?php

return [
    'timezone' => env('INTERNSHIP_TIMEZONE', 'Asia/Manila'),
    // Non-working days (Y-m-d, comma-separated) excluded from monitoring working-day counts (spec §13).
    'holidays' => array_values(array_filter(array_map('trim', explode(',', (string) env('INTERNSHIP_HOLIDAYS', ''))))),
    // Spec §17A-B decision #9: emailed one-time activation links, valid 72 hours by default.
    'activation_hours' => (int) env('ACCOUNT_ACTIVATION_HOURS', 72),

    // Student numbers whose ACCOUNT survives internmatch:purge-synthetic (their synthetic records are still removed).
    'synthetic_keep_student_numbers' => array_values(array_filter(array_map('trim', explode(',', (string) env('SYNTHETIC_KEEP_STUDENT_NUMBERS', '150001'))))),
    // Base URL of the portal used in activation e-mails.
    'portal_url' => rtrim(env('PORTAL_URL', 'http://localhost:8080'), '/'),
];
