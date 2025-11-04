<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Contracts;

use On1kel\OAS\Builder\Security\SecurityRequirement;
use On1kel\OAS\Builder\Security\SecurityScheme;

interface SecuritySchemesContainerContract
{
    /**
     * Возвращает набор securitySchemes для components.securitySchemes.
     * Ключ массива — имя схемы, значение — билдер SecurityScheme.
     *
     * @return array<string, SecurityScheme>
     */
    public static function getSecuritySchemes(): array;

    /**
     * Глобальные требования безопасности (OpenAPI `security` на корне документа).
     * Можно вернуть:
     *   - массив билдеров SecurityRequirement,
     *   - и/или ассоциативные карты вида ['SchemeName' => ['scope1','scope2']].
     *
     * Пример: [ SecurityRequirement::create()->add('ApiAuthSecurityScheme') ]
     *
     * @return array<int, SecurityRequirement|array<string, string[]>>
     */
    public static function getDefaultSecurity(): array;
}
