<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Attributes;

use InvalidArgumentException;
use Khazhinov\HyperfFlyDocs\Generator\Contracts\ComplexFactoryInterface;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Complex
{
    /**
     * @var class-string<ComplexFactoryInterface>
     */
    public string $factory;

    /** @var array<string, mixed> */
    public array $arguments = [];

    /**
     * @param class-string<ComplexFactoryInterface> $factory
     * @param array<string, mixed>                  $arguments
     */
    public function __construct(string $factory, ... $arguments)
    {
        if (! class_exists($factory)) {
            throw new InvalidArgumentException(sprintf(
                'Класс фабрики "%s" не найден. Используйте полное имя класса с ::class.',
                $factory
            ));
        }

        /** @phpstan-ignore-next-line */
        if (! is_a($factory, ComplexFactoryInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Класс фабрики "%s" должен реализовать %s.',
                $factory,
                ComplexFactoryInterface::class
            ));
        }
        $this->factory   = $factory;
        $this->arguments = $arguments;
    }
}
