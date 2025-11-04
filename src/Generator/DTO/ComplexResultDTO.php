<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;
use On1kel\OAS\Builder\Bodies\RequestBody;
use On1kel\OAS\Builder\Parameters\Parameter;
use On1kel\OAS\Builder\Responses\Responses;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

/**
 * Результат фабрики Complex в строгих DTO.
 */
final class ComplexResultDTO extends DataTransferObject
{
    public ?RequestBody $request_body = null;

    /**
     * @var array<Parameter|string>
     */
    #[CastWith(ArrayCaster::class, itemType: Parameter::class)]
    public array $parameters = [];
    public Responses $responses;

    /**
     * @var array<int|string,mixed>
     */
    public array $extensions = [];
}
