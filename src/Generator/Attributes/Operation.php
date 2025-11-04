<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Attributes;

use Attribute;

/**
 * Помечает метод контроллера как документируемый endpoint.
 * Отсутствие этого атрибута означает, что операция в документацию не попадёт.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Operation
{
    /**
     * @param string[]                          $tags
     * @param array<string, array<int, string>> $security
     */
    public function __construct(
        public array $tags = [],
        public bool $deprecated = false,
        public array $security = [],
    ) {
    }
}
