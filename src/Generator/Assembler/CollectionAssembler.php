<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Assembler;

use On1kel\HyperfFlyDocs\Generator\Contracts\RouteExtractorInterface;
use On1kel\HyperfFlyDocs\Generator\DTO\CollectionAssembleResultDTO;
use On1kel\HyperfFlyDocs\Generator\DTO\ConfigDTO;
use On1kel\HyperfFlyDocs\Generator\DTO\OperationContextDTO;
use On1kel\HyperfFlyDocs\Generator\DTO\RouteDTO;
use On1kel\HyperfFlyDocs\Generator\Registry\ComponentsRegistry;
use On1kel\HyperfFlyDocs\Generator\Services\ComplexRunner;
use On1kel\HyperfFlyDocs\Generator\Services\OperationComposer;
use On1kel\HyperfFlyDocs\Generator\Services\OperationMetaResolver;
use On1kel\HyperfFlyDocs\Generator\Services\PathsAccumulator;
use On1kel\HyperfFlyDocs\Generator\Services\RouteFilter;
use On1kel\HyperfFlyDocs\Generator\Services\SecurityDefinitionsApplier;
use ReflectionException;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

final class CollectionAssembler
{
    public function __construct(
        private readonly RouteExtractorInterface $route_extractor,
        private readonly RouteFilter $route_filter,
        private readonly OperationMetaResolver $meta_resolver,
        private readonly ComplexRunner $complex_runner,
        private readonly OperationComposer $composer,
        private readonly PathsAccumulator $paths_accumulator,
        private readonly ComponentsRegistry $components_registry,
        private readonly SecurityDefinitionsApplier $security_applier,
    ) {
    }

    /**
     * @param  ConfigDTO                   $collection_cfg
     * @throws ReflectionException
     * @throws UnknownProperties
     * @return CollectionAssembleResultDTO
     */
    public function assemble(ConfigDTO $collection_cfg): CollectionAssembleResultDTO
    {
        $raw_routes = $this->route_extractor->extract();

        $routes = [];
        foreach ($raw_routes as $rawRoute) {
            $norm = $this->normalizeRoute($rawRoute);
            if ($norm === null) {
                continue;
            }
            $routes[] = new RouteDTO($norm);
        }

        $filtered_routes = $this->route_filter->filter($routes, $collection_cfg);

        $used_tags = [];
        foreach ($filtered_routes as $route) {
            $meta = $this->meta_resolver->resolve($route);
            if ($meta === null) {
                continue;
            }

            $complex = $this->complex_runner->run($route);
            $context = new OperationContextDTO($route, $meta, $complex);

            $op = $this->composer->compose($context, $this->components_registry);

            $this->paths_accumulator->addOperation(
                $route->method,
                $route->path,
                $op,
                $route->path_params
            );

            foreach ($meta->tags as $tag) {
                $used_tags[$tag] = true;
            }
        }

        $securityRequirements = $this->security_applier->apply(
            $collection_cfg->security,
            $this->components_registry
        );

        $paths_builder = $this->paths_accumulator->toBuilder();
        $components_builder = $this->components_registry->toBuilder();

        $tags = array_keys($used_tags);
        sort($tags);

        return new CollectionAssembleResultDTO([
            'paths'      => $paths_builder,
            'components' => $components_builder,
            'used_tags'  => $tags,
            'info'       => $collection_cfg->info,
            'servers'    => $collection_cfg->servers,
            'security'   => $securityRequirements,
            'extensions' => $collection_cfg->extensions,
        ]);
    }

    /**
     * @param  array|object              $route
     * @return array<string, mixed>|null
     */
    private function normalizeRoute(array|object $route): ?array
    {
        $get = static function (array|object $src, string $key, mixed $default = null): mixed {
            if (is_array($src)) {
                return $src[$key] ?? $default;
            }

            return $src->$key ?? $default;
        };

        $method_raw = $get($route, 'httpMethod') ?? $get($route, 'method') ?? $get($route, 'http_method');
        $method = $method_raw ? strtolower((string)$method_raw) : 'get';

        $path = (string)($get($route, 'path') ?? '/');

        $controller = (string)($get($route, 'controller') ?? '');
        $action = (string)($get($route, 'action') ?? '');

        $server = (string)($get($route, 'serverName') ?? $get($route, 'server') ?? 'http');

        $path_params = $get($route, 'path_params', []);

        if ($controller === '' || $action === '') {
            return null;
        }

        return [
            'server'       => $server,
            'method'       => $method,
            'path'         => $path,
            'controller'   => $controller,
            'action'       => $action,
            'path_params'  => $path_params,
        ];
    }
}
