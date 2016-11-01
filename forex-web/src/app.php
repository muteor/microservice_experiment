<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Exception\ClientException;

require '../vendor/autoload.php';

$app = new \Slim\App;

// Lazy...
function escape_html($string) {
    return htmlspecialchars(
        $string,
        ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED,
        'UTF-8',
        true
    );
}

$container = $app->getContainer();
$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/');
};

$app->get('/', function (Request $request, Response $response) {
    $get = $request->getQueryParams();

    $result = false;
    if (!empty($get['convert'])) {
        try {
            // Fail fast client setup! Though timeouts should be based on real metrics :)
            $client = new GuzzleHttp\Client([
                'base_uri' => 'http://localhost:4141',
                'timeout' => 1.0,
                'connect_timeout' => 0.5,
                'headers' => [
                    'User-Agent' => 'forex-web/1.0',
                    'Accept' => 'application/json',
                ],
            ]);
            $query = http_build_query($request->getQueryParams());
            $res = $client->request('GET', '/convert?' . $query, [
                'headers' => [
                    'Host' => 'forex-currency-converter-4140' // routes linkerd to correct host
                ]
            ]);
            $result = json_decode($res->getBody(), true);

        } catch(ClientException $e) {
            $response = $this->view->render($response, "error.phtml", [
                    'response' => json_decode($e->getResponse()->getBody(), true),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
            ]);
            return $response;
        } catch(Exception $e) {
            $response = $this->view->render($response, "error.phtml", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $response;
        }
    }

    $response = $this->view->render($response, "index.phtml", [
        'result' => $result,
        'date' => $get['date'] ?? null,
        'from' => $get['from'] ?? null,
        'to' => $get['to'] ?? null,
        'amount' => $get['amount'] ?? null,
        'iso_codes' => explode(' ', 'USD JPY BGN CZK DKK GBP HUF PLN RON SEK CHF NOK HRK RUB TRY AUD BRL CAD CNY HKD IDR ILS INR KRW MXN MYR NZD PHP SGD THB ZAR'),
    ]);
    return $response;
});
$app->run();
