<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Http\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Http\Message\ResponseInterface;

#[Controller]
final class WellKnownController
{
    public function __construct(private readonly HttpResponse $response)
    {
    }

    // Отдаём 204, чтобы не было 404 в логах
    #[GetMapping(path: '/.well-known/appspecific/com.chrome.devtools.json')]
    public function chromeDevtools(): ResponseInterface
    {
        return $this->response->raw('')->withStatus(204);
    }
}
