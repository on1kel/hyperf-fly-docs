<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Extractor;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PatchMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use On1kel\HyperfFlyDocs\Generator\Contracts\RouteExtractorInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class CollectorRouteExtractor implements RouteExtractorInterface
{
    public function extract(): array
    {
        $routes = [];

        $controllers = AnnotationCollector::getClassesByAnnotation(Controller::class);
        $prefixes = [];
        $controllersOrder = [];

        foreach ($controllers as $class => $ann) {
            $prefix = $ann->prefix;
            $prefixes[$class] = '/' . ltrim($prefix, '/');

            try {
                $rc = new ReflectionClass($class);
                $controllersOrder[$class] = $rc->getStartLine() ?: PHP_INT_MAX;
            } catch (Throwable) {
                $controllersOrder[$class] = PHP_INT_MAX;
            }
        }

        $map = [
            'get' => GetMapping::class,
            'post' => PostMapping::class,
            'put' => PutMapping::class,
            'patch' => PatchMapping::class,
            'delete' => DeleteMapping::class,
        ];

        foreach ($map as $http => $annClass) {
            $methods = AnnotationCollector::getMethodsByAnnotation($annClass);

            foreach ($methods as $value) {
                $class = $value['class'];
                $method = $value['method'];
                $ann = $value['annotation'];

                if (!isset($prefixes[$class])) {
                    continue;
                }

                $prefix = $prefixes[$class];

                $path = $this->buildPath($prefix, $ann->path);

                $routes[] = $this->row(
                    $class,
                    $method,
                    $http,
                    $path,
                    $controllersOrder[$class] ?? PHP_INT_MAX
                );
            }
        }

        usort($routes, function ($a, $b) {
            $c = $a['_class_order'] <=> $b['_class_order'];
            if ($c !== 0) {
                return $c;
            }

            $c = $a['_order'] <=> $b['_order'];
            if ($c !== 0) {
                return $c;
            }

            $c = strcmp($a['controller'], $b['controller']);
            if ($c !== 0) {
                return $c;
            }
            $c = strcmp($a['action'], $b['action']);
            if ($c !== 0) {
                return $c;
            }

            return strcmp($a['path'], $b['path']);
        });

        foreach ($routes as &$r) {
            unset($r['_order'], $r['_class_order']);
        }

        return $routes;
    }

    private function buildPath(string $prefix, string $path): string
    {
        $full = rtrim(sprintf('%s/%s', rtrim($prefix, '/'), ltrim($path, '/')), '/');

        return $full === '' ? '/' : $full;
    }

    private function row(string $class, string $method, string $http, string $path, int $classOrder): array
    {
        $order = PHP_INT_MAX;
        try {
            $rm = new ReflectionMethod($class, $method);
            $order = $rm->getStartLine() ?: PHP_INT_MAX;
        } catch (Throwable) {
        }

        return [
            'server' => 'http',
            'httpMethod' => $http,
            'path' => $path,
            'handler' => $class . '@' . $method,
            'controller' => $class,
            'action' => $method,
            'path_params' => [],  // Можно добавить парсинг {param}
            '_class_order' => $classOrder,
            '_order' => $order,
        ];
    }
}
