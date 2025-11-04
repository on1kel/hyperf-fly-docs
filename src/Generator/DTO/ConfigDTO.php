<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;
use On1kel\HyperfFlyDocs\Generator\Contracts\SecuritySchemesContainerContract;

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
