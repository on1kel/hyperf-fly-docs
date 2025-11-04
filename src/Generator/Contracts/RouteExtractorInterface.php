<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Contracts;

interface RouteExtractorInterface
{
    /**
     * @return array<int, array{
     *   httpMethod: string,
     *   path: string,
     *   handler: string,
     *   controller: string,
     *   action: string
     * }>
     */
    public function extract(): array;
}
