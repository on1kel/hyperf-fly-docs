<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Services;

use On1kel\HyperfFlyDocs\Generator\Attributes\Operation;
use On1kel\HyperfFlyDocs\Generator\DTO\OperationMetaDTO;
use On1kel\HyperfFlyDocs\Generator\DTO\RouteDTO;
use On1kel\OAS\Builder\Security\SecurityRequirement as SecReq;
use ReflectionException;
use ReflectionMethod;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

final class OperationMetaResolver
{
    /**
     * @param  RouteDTO              $ctx
     * @throws ReflectionException
     * @throws UnknownProperties
     * @return OperationMetaDTO|null
     */
    public function resolve(RouteDTO $ctx): ?OperationMetaDTO
    {
        $rm = $this->reflect($ctx->controller, $ctx->action);
        if ($rm === null) {
            return null;
        }

        $opAttr = $this->firstAttr($rm, Operation::class);
        if (!$opAttr instanceof Operation) {
            return null;
        }

        [$summary, $description] = $this->extractPhpDoc($rm);

        /** @var list<SecReq> $security */
        $security = $this->normalizeSecurity($opAttr->security);

        return new OperationMetaDTO([
            'tags' => $opAttr->tags,
            'summary' => $summary ?? '',
            'description' => $description ?? '',
            'deprecated' => $opAttr->deprecated,
            'security' => $security,
            'extensions' => [],
        ]);
    }

    private function reflect(string $controller, string $action): ?ReflectionMethod
    {
        if (!class_exists($controller) || !method_exists($controller, $action)) {
            return null;
        }

        return new ReflectionMethod($controller, $action);
    }

    private function firstAttr(ReflectionMethod $rm, string $fqcn): ?object
    {
        $attrs = $rm->getAttributes($fqcn);

        if ($attrs === []) {
            return null;
        }

        return $attrs[0]->newInstance();
    }

    /**
     * Парсинг PHPDoc для summary/description.
     *
     * @return array{0: string|null, 1: string|null} // ← уточняем value types
     */
    private function extractPhpDoc(ReflectionMethod $rm): array
    {
        $doc = $rm->getDocComment();
        if ($doc === false) {
            return [null, null];
        }

        $docClean = preg_replace(['#^/\*\*#', '#\*/$#'], '', $doc);
        if ($docClean === null) {
            $docClean = $doc;
        }

        $lines = explode("\n", $docClean);
        $summary = '';
        $description = '';
        $inDescription = false;

        foreach ($lines as $line) {
            $line = trim(preg_replace('/^\s*\* ?/', '', $line) ?? '');

            if ($line === '' || str_starts_with($line, '@')) {
                if ($inDescription) {
                    break;
                }
                continue;
            }

            if ($summary === '') {
                $summary = $line;
            } else {
                $inDescription = true;
                $description .= $line . "\n";
            }
        }

        return [trim($summary), trim($description)];
    }

    /**
     * Нормализует security в список SecurityRequirement билдов.
     *
     * @param  mixed        $security
     * @return list<SecReq> // [94] уточняем value type
     */
    private function normalizeSecurity(mixed $security): array
    {
        if (!is_array($security)) {
            return [];
        }

        /** @var list<array<string, list<string>>> $requirements */
        $requirements = [];

        if ($this->isAssoc($security)) {
            $requirements[] = $this->normalizeRequirementMap($security);
        } else {
            foreach ($security as $req) {
                if (is_array($req)) {
                    $requirements[] = $this->normalizeRequirementMap($req);
                }
            }
        }

        $out = [];
        foreach ($requirements as $reqMap) {
            $builder = SecReq::create();
            foreach ($reqMap as $scheme => $scopes) {
                $builder->add($scheme, ...$scopes);
            }
            $out[] = $builder;
        }

        return $out;
    }

    /**
     * Преобразует карту {scheme: scopes} к виду array<string, list<non-empty-string>>.
     *
     * @param  array<array-key, mixed>               $map
     * @return array<string, list<non-empty-string>>
     */
    private function normalizeRequirementMap(array $map): array
    {
        $norm = [];
        foreach ($map as $scheme => $scopes) {
            if (!is_string($scheme) || $scheme === '') {
                continue;
            }

            /** @var list<non-empty-string> $lst */
            $lst = [];
            if (is_array($scopes)) {
                foreach ($scopes as $s) {
                    if (is_string($s) && $s !== '') {
                        $lst[] = $s;
                    }
                }
            } elseif (is_string($scopes) && $scopes !== '') {
                $lst[] = $scopes;
            }

            $norm[$scheme] = $lst;
        }

        return $norm;
    }

    /**
     * @param array<array-key, mixed> $arr
     */
    private function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
