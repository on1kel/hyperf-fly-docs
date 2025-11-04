<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Registry;

use On1kel\OAS\Builder\Bodies\RequestBody;
use On1kel\OAS\Builder\Components\Components;
use On1kel\OAS\Builder\Parameters\Parameter;
use On1kel\OAS\Builder\Responses\Response;
use On1kel\OAS\Builder\Schema\Schema;
use On1kel\OAS\Builder\Security\SecurityScheme;
use On1kel\OAS\Core\Model\Reference;
use RuntimeException;

final class ComponentsRegistry
{
    /** @var array<string, Schema> */
    private array $schemas = [];
    /** @var array<string, Parameter> */
    private array $parameters = [];
    /** @var array<string, Response> */
    private array $responses = [];
    /** @var array<string, RequestBody> */
    private array $requestBodies = [];
    /** @var array<string, SecurityScheme> */
    private array $securitySchemes = [];

    /** @var array<string, Reference> */
    private array $refs = [];

    // ──────────────────────── SCHEMAS ─────────────────────────

    public function getOrRegisterSchema(string $name, callable $factory): Reference
    {
        $normalized = $this->normalizeName($name);

        if (isset($this->schemas[$normalized])) {
            return $this->getRefFor('schemas', $normalized);
        }

        $schema = $factory();
        if (!$schema instanceof Schema) {
            throw new RuntimeException("Фабрика для схемы \"{$normalized}\" должна вернуть экземпляр Schema");
        }

        $this->schemas[$normalized] = $schema;

        return $this->getRefFor('schemas', $normalized);
    }

    public function ensureSchema(string $name, Schema $schema): Reference
    {
        $normalized = $this->normalizeName($name);
        $this->schemas[$normalized] ??= $schema;

        return $this->getRefFor('schemas', $normalized);
    }

    // ──────────────────────── PARAMETERS ──────────────────────

    public function ensureParameter(string $name, Parameter $param): Reference
    {
        $normalized = $this->normalizeName($name);
        $this->parameters[$normalized] ??= $param;

        return $this->getRefFor('parameters', $normalized);
    }

    // ──────────────────────── RESPONSES ───────────────────────

    public function ensureResponse(string $name, Response $response): Reference
    {
        $normalized = $this->normalizeName($name);
        $this->responses[$normalized] ??= $response;

        return $this->getRefFor('responses', $normalized);
    }

    // ──────────────────────── REQUEST BODIES ──────────────────

    public function ensureRequestBody(string $name, RequestBody $body): Reference
    {
        $normalized = $this->normalizeName($name);
        $this->requestBodies[$normalized] ??= $body;

        return $this->getRefFor('requestBodies', $normalized);
    }

    // ──────────────────────── SECURITY SCHEMES ────────────────

    public function ensureSecurityScheme(string $name, SecurityScheme $scheme): Reference
    {
        $normalized = $this->normalizeName($name);
        $this->securitySchemes[$normalized] ??= $scheme;

        return $this->getRefFor('securitySchemes', $normalized);
    }

    // ──────────────────────── EXPORT ──────────────────────────

    public function toBuilder(): Components
    {
        $components = Components::create();

        foreach ($this->schemas as $name => $schema) {
            $components = $components->schema($name, $schema);
        }

        foreach ($this->parameters as $name => $param) {
            $components = $components->parameter($name, $param);
        }

        foreach ($this->responses as $name => $resp) {
            $components = $components->response($name, $resp);
        }

        foreach ($this->requestBodies as $name => $body) {
            $components = $components->requestBody($name, $body);
        }

        foreach ($this->securitySchemes as $name => $scheme) {
            $components = $components->securityScheme($name, $scheme);
        }

        return $components;
    }

    // ──────────────────────── ВСПОМОГАТЕЛЬНЫЕ ────────────────

    private function normalizeName(string $raw): string
    {
        $raw = ltrim($raw, '\\');
        if (str_contains($raw, '\\')) {
            $raw = substr($raw, strrpos($raw, '\\') + 1);
        }

        return preg_replace('/[^A-Za-z0-9_]/', '_', $raw) ?? 'Model';
    }

    private function getRefFor(string $section, string $name): Reference
    {
        $key = "{$section}.{$name}";
        if (!isset($this->refs[$key])) {
            $this->refs[$key] = Schema::ref("#/components/{$section}/{$name}");
        }

        return $this->refs[$key];
    }

    // ──────────────────────── GETTERS ─────────────────────────

    public function allSchemas(): array
    {
        return $this->schemas;
    }

    public function allParameters(): array
    {
        return $this->parameters;
    }

    public function allResponses(): array
    {
        return $this->responses;
    }

    public function allRequestBodies(): array
    {
        return $this->requestBodies;
    }

    public function allSecuritySchemes(): array
    {
        return $this->securitySchemes;
    }
}
