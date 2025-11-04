<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\DTO;

use Khazhinov\HyperfFlyDocs\Generator\Contracts\SecuritySchemesContainerContract;
use Khazhinov\PhpSupport\DTO\DataTransferObject;

final class ConfigDTO extends DataTransferObject
{
    /** @var string[] */
    public array $include_tags = ['*'];

    /** @var array{title:string,version:string,description?:string,contact?:array,license?:array} */
    public array $info = [
        'title' => 'API',
        'version' => '1.0.0',
        'description' => '',
    ];

    /** @var array<int,array{url:string,description?:string}> */
    public array $servers = [];

    /**
     * Классы-контейнеры авторизации из конфига/коллекции (::class).
     * @var list<class-string<SecuritySchemesContainerContract>>
     */
    public array $security = [];

    /** @var array<int|string,mixed> */
    public array $extensions = [];
}
