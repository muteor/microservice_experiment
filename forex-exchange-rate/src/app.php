<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;

// /2016-01-01/EUR/GBP
$app->get('/{date:\d{4}-\d{2}-\d{2}}/{from_iso:[A-Z]{3}}/{to_iso:[A-Z]{3}}', function (Request $request, Response $response, $args) {
    // load the 90d rates from ECB
    $xml = simplexml_load_file(__DIR__ . '/../data/rates.xml');
    $xml->registerXPathNamespace('ecb', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');
    $rates = [];
    foreach ($xml->xpath("//ecb:Cube/ecb:Cube[@time='{$args['date']}']/ecb:Cube") as $cube) {
        $rates[(string) $cube['currency']] = (string) $cube['rate'];
    }

    $from_rate = $rates[$args['from_iso']] ?? null;
    $to_rate = $rates[$args['to_iso']] ?? null;
    
    if ($args['from_iso'] == 'EUR' && isset($rates[$args['to_iso']])) {
        $response->write(json_encode([
            'rate' => $rates[$args['to_iso']]
        ]));
        return $response;
    }
    
    if (!$from_rate || !$to_rate) {
        $response
            ->withStatus(400)
            ->write(json_encode([
                'error' => 'rate not found'
            ]));
        return $response;
    }

    $response->write(json_encode([
        'rate' => (string) ($to_rate / $from_rate)
    ]));
    
    return $response;
});
$app->run();
