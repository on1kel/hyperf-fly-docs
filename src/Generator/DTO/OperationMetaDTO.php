<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\DTO;

use Khazhinov\PhpSupport\DTO\DataTransferObject;
use On1kel\OAS\Builder\Security\SecurityRequirement;

/**
 * Метаданные операции, собранные из атрибутов и PHPDoc.
 * Содержит "внешнюю" информацию для OpenAPI — теги, описание, безопасность и пр.
 */
final class OperationMetaDTO extends DataTransferObject
{
    /**
     * @var list<string>
     */
    public array $tags = [];
    public string $summary = '';
    public string $description = '';
    public bool $deprecated = false;

    /**
     * @var list<SecurityRequirement>
     */
    public array $security = [];

    /**
     * @var array<string,mixed>
     */
    public array $extensions = [];
}
