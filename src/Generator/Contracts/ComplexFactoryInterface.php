<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Contracts;

use On1kel\HyperfFlyDocs\Generator\DTO\ComplexResultDTO;

interface ComplexFactoryInterface
{
    /**
     * @param mixed $arguments
     *
     * @return ComplexResultDTO
     */
    public function build(... $arguments): ComplexResultDTO;
}
