<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Services;

use Hyperf\Context\ApplicationContext;
use On1kel\HyperfFlyDocs\Generator\Attributes\Complex;
use On1kel\HyperfFlyDocs\Generator\Contracts\ComplexFactoryInterface;
use On1kel\HyperfFlyDocs\Generator\DTO\ComplexResultDTO;
use On1kel\HyperfFlyDocs\Generator\DTO\RouteDTO;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

final class ComplexRunner
{
    public function run(RouteDTO $route): ComplexResultDTO
    {
        $refMethod = $this->getControllerMethodReflection($route);

        $complexAttr = $this->extractComplexAttribute($refMethod, $route);

        $factoryClass = $complexAttr->factory;

        $container = ApplicationContext::getContainer();
        $factory = $container->get($factoryClass);

        if (!$factory instanceof ComplexFactoryInterface) {
            throw new RuntimeException("Фабрика {$factoryClass} должна реализовывать " . ComplexFactoryInterface::class);
        }

        $arguments = $complexAttr->arguments;

        return $factory->build(...$arguments);
    }

    private function getControllerMethodReflection(RouteDTO $route): ReflectionMethod
    {
        $refClass = new ReflectionClass($route->controller);

        if (!$refClass->hasMethod($route->action)) {
            throw new RuntimeException("Метод {$route->controller}::{$route->action} не найден.");
        }

        return $refClass->getMethod($route->action);
    }

    private function extractComplexAttribute(ReflectionMethod $refMethod, RouteDTO $route): Complex
    {
        $attributes = $refMethod->getAttributes(Complex::class);

        if (count($attributes) === 0) {
            throw new RuntimeException("У {$route->controller}::{$route->action} отсутствует #[Complex(...)]");
        }

        return $attributes[0]->newInstance();
    }
}
