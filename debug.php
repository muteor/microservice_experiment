<?php

echo json_encode([
    'request' => $_REQUEST,
    'server' => $_SERVER,
    'amount' => '£23.45',
    'converted' => '$160.33',
    'from_iso_code' => 'GBP',
    'to_iso_code' => 'USD',
    'rate' => '1.453344',
    'date' => '2016-01-01',
]);


// /http/1.1/GET/forex-exchange-rate-4140/convert