<?php

declare(strict_types=1);

namespace Keboola\ComponentBulkModify;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class AdminApiClient
{
    private const JSON_DEPTH = 512;
    private const LIST_LIMIT = 100;

    private Client $guzzle;

    public function __construct(string $url, string $token, array $options = [])
    {
        if (empty($url)) {
            throw new \RuntimeException('Url is empty.');
        }
        if (empty($token)) {
            throw new \RuntimeException('Token is empty.');
        }
        if (empty($options['backoffMaxTries'])) {
            $options['backoffMaxTries'] = 3;
        }
        if (empty($options['userAgent'])) {
            $options['userAgent'] = 'ComponentBulkModify';
        }
        $this->guzzle = $this->initClient($url, $token, $options);
    }

    private function initClient(string $url, string $token, array $options = []): Client
    {
        $handlerStack = HandlerStack::create();
        // Set exponential backoff
        $handlerStack->push(Middleware::retry($this->createDefaultDecider($options['backoffMaxTries'])));
        // Set handler to set default headers
        $handlerStack->push(Middleware::mapRequest(
            function (RequestInterface $request) use ($token, $options) {
                return $request
                    ->withHeader('User-Agent', $options['userAgent'])
                    ->withHeader('Authorization', $token)
                    ->withHeader('Content-type', 'application/json');
            }
        ));
        // Set client logger
        if (isset($options['logger']) && $options['logger'] instanceof LoggerInterface) {
            $handlerStack->push(Middleware::log(
                $options['logger'],
                new MessageFormatter(
                    '{hostname} {req_header_User-Agent} - [{ts}] "{method} {resource} {protocol}/{version}"' .
                    ' {code} {res_header_Content-Length}'
                )
            ));
        }
        // finally create the instance
        return new Client(['base_uri' => $url, 'handler' => $handlerStack]);
    }

    private function createDefaultDecider(int $maxRetries): Closure
    {
        return function (
            $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            $error = null
        ) use ($maxRetries) {
            if ($retries >= $maxRetries) {
                return false;
            } elseif ($response && $response->getStatusCode() >= 500) {
                return true;
            } elseif ($error) {
                return true;
            } else {
                return false;
            }
        };
    }

    public function getAllAdminApps(): array
    {
        $offset = 0;
        $apps = [];
        do {
            $request = new Request('GET', sprintf('admin/apps/?offset=%s&limit=%s', $offset, self::LIST_LIMIT));
            $response = $this->guzzle->send($request);
            $data = json_decode($response->getBody()->getContents(), true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
            $apps = array_merge($apps, $data);
            $offset += self::LIST_LIMIT;
        } while (count($data) === self::LIST_LIMIT);
        return $apps;
    }

    public function patchApp(string $appId, array $patchData): array
    {
        if (empty($appId)) {
            throw new \RuntimeException('AppId is empty');
        }
        $request = new Request('PATCH', sprintf('admin/apps/%s', $appId), [], (string) json_encode($patchData));
        $response = $this->guzzle->send($request);
        return json_decode($response->getBody()->getContents(), true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
    }
}
