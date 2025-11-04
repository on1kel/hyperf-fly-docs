<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Services;

use Hyperf\Contract\ConfigInterface;
use On1kel\HyperfFlyDocs\Generator\Contracts\SecuritySchemesContainerContract;
use On1kel\HyperfFlyDocs\Generator\Registry\ComponentsRegistry;
use On1kel\OAS\Builder\Security\SecurityRequirement;

final class SecurityDefinitionsApplier
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {
    }

    /**
     * Регистрирует securitySchemes в ComponentsRegistry и возвращает
     * мердж глобальных + локальных SecurityRequirement (builders).
     *
     * @param  array<class-string<SecuritySchemesContainerContract>> $localContainers
     * @return list<SecurityRequirement>
     */
    public function apply(array $localContainers, ComponentsRegistry $components): array
    {
        $global = (array) ($this->config->get('fly-docs.security_definitions.security_schemes') ?? []);
        $classes = \array_values(\array_unique([...$global, ...$localContainers]));

        $requirements = [];

        foreach ($classes as $class) {
            if (!\is_string($class) || !\class_exists($class)) {
                continue;
            }
            if (!\is_subclass_of($class, SecuritySchemesContainerContract::class)) {
                continue;
            }

            /** @var class-string<SecuritySchemesContainerContract> $class */

            // 1) схемы → components
            $schemes = $class::getSecuritySchemes();
            foreach ($schemes as $name => $schemeBuilder) {
                $components->ensureSecurityScheme($name, $schemeBuilder);
            }

            // 2) требования → список билдера
            foreach ((array) $class::getDefaultSecurity() as $req) {
                if ($req instanceof SecurityRequirement) {
                    $requirements[] = $req;
                } elseif (\is_array($req)) {
                    $requirements[] = SecurityRequirement::create()->set($req);
                }
            }
        }

        // Уберём дубликаты по сериализации
        $uniq = [];
        $out  = [];
        foreach ($requirements as $r) {
            $key = \json_encode($r->toModel(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!isset($uniq[$key])) {
                $uniq[$key] = true;
                $out[] = $r;
            }
        }

        return $out;
    }
}
