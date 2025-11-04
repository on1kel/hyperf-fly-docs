<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Contracts;

use Khazhinov\HyperfFlyDocs\Generator\DTO\ComplexResultDTO;

interface ComplexFactoryInterface
{
    /**
     * @param mixed $arguments
     *
     * @return ComplexResultDTO
     */
    public function build(... $arguments): ComplexResultDTO;
}
