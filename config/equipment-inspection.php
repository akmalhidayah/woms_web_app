<?php

return [
    'require_unit_for_new_inspections' => (bool) env('EQUIPMENT_INSPECTION_REQUIRE_UNIT', false),
    'public_base_url' => env('EQUIPMENT_INSPECTION_PUBLIC_BASE_URL') ?: env('APP_URL'),
    'public_history_per_page' => 10,
];
