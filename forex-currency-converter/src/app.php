<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Money\Currencies\ISOCurrencies;
use Money\Parser\IntlMoneyParser;
use Money\Formatter\IntlMoneyFormatter;
use Money\Currency;
use Money\CurrencyPair;

require '../vendor/autoload.php';

$app = new \Slim\App;

$app->get('/convert', function (Request $request, Response $response) {
    $date = $request->getQueryParams()['date'];
    $from = $request->getQueryParams()['from'];
    $to = $request->getQueryParams()['to'];
    $amount = $request->getQueryParams()['amount'];
    
    try {
        // Fail fast client setup! Though timeouts should be based on real metrics :)
        $client = new GuzzleHttp\Client([
            'base_uri' => 'http://localhost:4141', // outgoing local linkerd
            'timeout' => 1.0,
            'connect_timeout' => 0.5,
            'headers' => [
                'User-Agent' => 'forex-currency-converter/1.0',
                'Accept' => 'application/json',
            ],
        ]);
        
        $res = $client->request('GET', "{$date}/{$from}/{$to}", [
            'headers' => [
                'Host' => 'forex-exchange-rate-4140' // routes linkerd to correct host
            ]
        ]);
        $result = json_decode($res->getBody(), true);
        
        if (isset($result['error'])) {
            $response
                ->withStatus(400)
                ->write(json_encode([
                    'error' => 'rate not found'
                ]));
            return $response;
        }

        $rate = $result['rate'];

        $currencies = new ISOCurrencies();
        $numberFormatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);
        $moneyParser = new IntlMoneyParser($numberFormatter, $currencies);

        $currencyFrom = new Currency($from);
        $currencyTo   = new Currency($to);

        // Parses the amount to 2 decimal places
        $fromAmount = $moneyParser->parse("£{$amount}", $from);

        // Exchange the pair
        $pair = new CurrencyPair($currencyFrom, $currencyTo, $rate);
        $converted = $pair->convert($fromAmount);

        $response->write(json_encode([
            'amount' => $moneyFormatter->format($fromAmount),
            'converted' => $moneyFormatter->format($converted),
            'from_iso_code' => $from,
            'to_iso_code' => $to,
            'rate' => $rate,
            'date' => $date,
            'server' => $_SERVER,
            'request' => $_REQUEST,
        ], JSON_PRETTY_PRINT));
        return $response;

    } catch(Exception $e) {
        return $response->write('Fail currency converter' . $e->getMessage());
    }
});
$app->run();
