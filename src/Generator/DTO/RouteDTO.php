<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;

final class RouteDTO extends DataTransferObject
{
    public string $server;
    public string $method;
    public string $path;
    public string $controller;
    public string $action;
    public array $path_params = [];
}
