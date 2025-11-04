<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Services;

use function in_array;

use On1kel\HyperfFlyDocs\Generator\DTO\ConfigDTO;
use On1kel\HyperfFlyDocs\Generator\DTO\RouteDTO;

final class RouteFilter
{
    public function __construct(
        private readonly OperationMetaResolver $metaResolver
    ) {
    }

    /**
     * @param  RouteDTO[] $routes
     * @param  ConfigDTO  $cfg
     * @return RouteDTO[]
     */
    public function filter(array $routes, ConfigDTO $cfg): array
    {
        $includeTags = $cfg->include_tags ?? ['*'];
        $includeAll  = in_array('*', $includeTags, true);

        $result = [];

        foreach ($routes as $route) {
            $meta = $this->metaResolver->resolve($route);

            if ($meta === null) {
                continue;
            }

            $routeTagNames = $meta->tags;

            if (
                $includeAll
                || !empty(array_intersect($routeTagNames, $includeTags))
            ) {
                $result[] = $route;
            }
        }

        return $result;
    }
}
