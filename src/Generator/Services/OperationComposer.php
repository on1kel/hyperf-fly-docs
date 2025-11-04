<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Services;

use Khazhinov\HyperfFlyDocs\Generator\DTO\OperationContextDTO;
use Khazhinov\HyperfFlyDocs\Generator\DTO\OperationMetaDTO;
use Khazhinov\HyperfFlyDocs\Generator\Registry\ComponentsRegistry;
use On1kel\OAS\Builder\Bodies\RequestBody;
use On1kel\OAS\Builder\Parameters\Parameter;
use On1kel\OAS\Builder\Paths\Operation as OperationBuilder;
use On1kel\OAS\Builder\Responses\Responses;
use On1kel\OAS\Builder\Security\SecurityRequirement;

final class OperationComposer
{
    public function compose(
        OperationContextDTO $context,
        ComponentsRegistry $components
    ): OperationBuilder {
        $meta = $context->meta;
        $complex = $context->complex;

        $op = OperationBuilder::create();

        // summary / description (строки)
        if ($meta->summary !== '') {
            $op = $op->summary($meta->summary);
        }
        if ($meta->description !== '') {
            $op = $op->description($meta->description);
        }

        // deprecated
        if ($meta->deprecated) {
            $op = $op->deprecated(true);
        }

        // tags: только непустые строки
        if ($meta->tags !== []) {
            foreach ($meta->tags as $tag) {
                if ($tag !== '') {
                    $op = $op->tags($tag);
                }
            }
        }

        // security
        if ($meta->security !== []) {
            foreach ($meta->security as $securityScheme) {
                $op = $op->securityRequirement($securityScheme);
            }
        }

        // parameters
        if (is_object($complex) && property_exists($complex, 'parameters')) {
            /** @var list<Parameter|string> $params */
            $params = $complex->parameters;
            if ($params !== []) {
                foreach ($params as $param) {
                    $op = $op->parameter($param);
                }
            }
        }

        // request body
        if (is_object($complex) && property_exists($complex, 'request_body')) {
            $rb = $complex->request_body;
            if ($rb instanceof RequestBody || is_string($rb)) {
                $op = $op->requestBody($rb);
            }
        }

        // responses
        if (is_object($complex) && property_exists($complex, 'responses') && $complex->responses instanceof Responses) {
            $op = $op->responses($complex->responses);
        }

        // extensions
        if ($meta->extensions !== []) {
            foreach ($meta->extensions as $extName => $extValue) {
                // $extName уже string по типу массива; проверим только непустоту
                if ($extName !== '') {
                    $op = $op->extension($extName, $extValue);
                }
            }
        }

        return $op;
    }
}
