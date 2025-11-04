<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\Custer\DataTransferObjectCaster;
use Khazhinov\PhpSupport\DTO\DataTransferObject;
use Spatie\DataTransferObject\Attributes\CastWith;

/**
 * Контекст конкретной операции API.
 * Содержит всю базовую информацию, необходимую для анализа метода.
 */
final class OperationContextDTO //extends DataTransferObject
{
    #[CastWith(DataTransferObjectCaster::class, dto_class: RouteDTO::class)]
    public RouteDTO $route;

    #[CastWith(DataTransferObjectCaster::class, dto_class: OperationMetaDTO::class)]
    public OperationMetaDTO $meta;
    public mixed $complex; // ComplexResultDTO

    public function __construct(
        RouteDTO $route,
        OperationMetaDTO $meta,
        ComplexResultDTO $complex
    ) {
        $this->route   = $route;
        $this->meta    = $meta;
        $this->complex = $complex;
    }
}
