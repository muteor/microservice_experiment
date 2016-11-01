<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$app = new \Slim\App;

// /2016-01-01/EUR/GBP
$app->get('/{date:\d{4}-\d{2}-\d{2}}/{from_iso:[A-Z]{3}}/{to_iso:[A-Z]{3}}', function (Request $request, Response $response, $args) {
    $requestedDate = new DateTime($args['date'], new DateTimeZone("Europe/London"));
    if ($requestedDate->format('N') >= 6) {
        $requestedDate->sub(new DateInterval('P' . (2 - (7 - $requestedDate->format('N'))) . 'D'));
    }

    // load the 90d rates from ECB
    $xml = simplexml_load_file(__DIR__ . '/../data/rates.xml');
    $xml->registerXPathNamespace('ecb', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');
    $rates = [];
    foreach ($xml->xpath("//ecb:Cube/ecb:Cube[@time='{$requestedDate->format('Y-m-d')}']/ecb:Cube") as $cube) {
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
