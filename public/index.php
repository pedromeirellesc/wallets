<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

[$container, $router] = require_once dirname(__DIR__) . '/bootstrap/app.php';

$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES,
);

$contentType = $request->getHeaderLine('Content-Type');

if (stripos($contentType, 'application/json') !== false) {
    $body = $request->getBody()->getContents();
    if (!empty($body)) {
        $parsedBody = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $request = $request->withParsedBody($parsedBody);
        }
    }
}

try {
    $response = $router->dispatch($request);
} catch (Exception $e) {
    $response = new JsonResponse(['error' => $e->getMessage()], 500);
}

(new SapiEmitter())->emit($response);
