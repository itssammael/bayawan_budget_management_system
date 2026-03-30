<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create(
        '/budget/aip', 'POST', [
            'budget_year_id' => 1,
            'budget_classification_id' => 1,
            'fund_source_id' => 1,
            'aip_reference_code' => 'TEST-1234',
            'ppa_description' => 'Test',
            'department_id' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'amount' => 100,
            // 'ppsa_id' => null,
        ]
    )
);
echo $response->getContent();
