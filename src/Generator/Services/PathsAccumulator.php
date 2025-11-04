<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Generator\Services;

use function array_key_exists;

use On1kel\OAS\Builder\Parameters\Parameter as ParameterBuilder;
use On1kel\OAS\Builder\Paths\Operation as OperationBuilder;
use On1kel\OAS\Builder\Paths\PathItem as PathItemBuilder;
use On1kel\OAS\Builder\Paths\Paths as PathsBuilder;
use On1kel\OAS\Builder\Schema\Schema;

final class PathsAccumulator
{
    /** @var array<string,PathItemBuilder> */
    private array $items = [];

    /**
     * @param array<int,array{name:string,required?:bool,description?:string,example?:mixed,schema?:Schema}> $pathParams
     */
    public function addOperation(
        string $method,
        string $path,
        OperationBuilder $operation,
        array $pathParams = [],
    ): void {
        $normalizedPath = $this->normalizePath($path);

        if (!isset($this->items[$normalizedPath])) {
            $this->items[$normalizedPath] = PathItemBuilder::create();
        }

        $item = $this->items[$normalizedPath];

        // 1) Привязываем операцию по HTTP-методу
        switch (strtolower($method)) {
            case 'get':     $item = $item->get($operation);
                break;
            case 'post':    $item = $item->post($operation);
                break;
            case 'put':     $item = $item->put($operation);
                break;
            case 'patch':   $item = $item->patch($operation);
                break;
            case 'delete':  $item = $item->delete($operation);
                break;
            case 'options': $item = $item->options($operation);
                break;
            case 'head':    $item = $item->head($operation);
                break;
            default: /* игнор */ break;
        }

        // 2) Сначала добавим явно переданные pathParams
        $declared = [];
        foreach ($pathParams as $param) {
            $name = $param['name'];
            if ($name === '') {
                continue;
            }
            $declared[strtolower($name)] = true;

            $pb = ParameterBuilder::path($name)
                ->required((bool)($param['required'] ?? true));

            if (!empty($param['description'])) {
                $pb = $pb->description($param['description']);
            }
            if (array_key_exists('example', $param)) {
                $pb = $pb->example($param['example']);
            }
            if (isset($param['schema'])) {
                /** @var Schema $schema */
                $schema = $param['schema'];
                $pb = $pb->schema($schema);
            }

            $item = $item->parameters($pb);
        }

        // 3) Автодобавим недостающие {vars} из шаблона пути
        foreach ($this->extractPathVars($normalizedPath) as $var) {
            $key = strtolower($var);
            if (isset($declared[$key])) {
                continue; // уже добавлен вручную выше
            }

            $schema = $this->guessSchemaForVar($normalizedPath, $var);

            $pb = ParameterBuilder::path($var)
                ->required(true)
                ->description('Параметр пути')
                ->schema($schema);

            $item = $item->parameters($pb);
        }

        $this->items[$normalizedPath] = $item;
    }

    public function toBuilder(): PathsBuilder
    {
        $pathsBuilder = PathsBuilder::create();
        foreach ($this->items as $path => $itemBuilder) {
            $pathsBuilder = $pathsBuilder->put($path, $itemBuilder);
        }

        return $pathsBuilder;
    }

    // ───────────────────────── helpers ─────────────────────────

    private function normalizePath(string $path): string
    {
        $clean = preg_replace('~//+~', '/', $path) ?? $path;
        if ($clean !== '/' && str_ends_with($clean, '/')) {
            $clean = rtrim($clean, '/');
        }

        return $clean;
    }

    /**
     * Достаёт имена плейсхолдеров {var} и {var:regex}
     * @return list<string>
     */
    private function extractPathVars(string $pathTemplate): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_\-]*)(?::[^}]+)?\}/', $pathTemplate, $m);
        /** @var string[] $vars */
        $vars = array_unique($m[1]);

        return array_values($vars);
    }

    /**
     * Эвристика выбора схемы по regex-ограничению/имени параметра
     */
    private function guessSchemaForVar(string $path, string $var): Schema
    {
        // Попробуем вытащить regex из {var:...}
        $regex = null;
        $pattern = sprintf('/\{%s:(.+?)\}/', preg_quote($var, '/'));
        if (preg_match($pattern, $path, $mm)) {
            $regex = $mm[1];
        }

        $lower = strtolower($var);

        // 1) Если в имени явный uuid
        if (str_contains($lower, 'uuid')) {
            return Schema::string()->asUUID();
        }

        // 2) Если имя оканчивается на id — чаще всего integer
        if (preg_match('/(^id$|_id$)/i', $var)) {
            return Schema::integer();
        }

        // 3) Эвристики по regex
        if (is_string($regex) && $regex !== '') {
            // числа: \d+, ^\d+$, [0-9]+
            if (preg_match('/(?:\\\\d|\[0-9\])\+|\^\d+\$?/', $regex)) {
                return Schema::integer();
            }
            // uuid в regex
            if (preg_match('/uuid|[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $regex)) {
                return Schema::string()->asUUID();
            }
            // «slug»-подобные ограничения (запрет . и /)
            if (preg_match('/\[\^\/\.]/', $regex)) {
                return Schema::string()->description('Slug');
            }
        }

        // 4) Фолбэк — строка
        return Schema::string();
    }
}
