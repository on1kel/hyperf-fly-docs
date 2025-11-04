<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;
use On1kel\OAS\Builder\Components\Components as ComponentsBuilder;
use On1kel\OAS\Builder\Paths\Paths as PathsBuilder;

/**
 * Результат сборки одной коллекции (например "latest").
 *
 * Используется между:
 *  - CollectionAssembler (который строит paths/components)
 *  - DocumentFactory (который собирает итоговый OpenAPI-документ)
 *
 * !!! Все поля обязательны и типизированы строго !!!
 */
final class CollectionAssembleResultDTO extends DataTransferObject
{
    public PathsBuilder $paths;
    public ComponentsBuilder $components;

    /** @var string[] */
    public array $used_tags;

    /** @var array{title:string,version:string,description?:string,contact?:array,license?:array} */
    public array $info;

    /** @var array<int,array{url:string,description?:string}> */
    public array $servers;

    /** @var array<int,mixed> */
    public array $security;

    /** @var array<int|string,mixed> */
    public array $extensions;
}
