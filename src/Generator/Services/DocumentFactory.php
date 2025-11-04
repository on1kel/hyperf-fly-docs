<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Services;

use Khazhinov\HyperfFlyDocs\Generator\DTO\CollectionAssembleResultDTO;
use On1kel\OAS\Builder\Info;
use On1kel\OAS\Builder\Info\Contact as ContactBuilder;
use On1kel\OAS\Builder\Info\License as LicenseBuilder;
use On1kel\OAS\Builder\OpenApi as OpenApiBuilder;
use On1kel\OAS\Builder\Servers\Server;
use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Builder\Tags\Tag;
use On1kel\OAS\Core\Model\OpenApiDocument;

final class DocumentFactory
{
    public function make(CollectionAssembleResultDTO $assembled): OpenApiDocument
    {
        $infoArr = $assembled->info;

        // Info
        $infoBuilder = Info::create()
            ->title($infoArr['title'] ?? 'API')
            ->version($infoArr['version'] ?? '1.0.0');

        if (!empty($infoArr['description'])) {
            $infoBuilder = $infoBuilder->description($infoArr['description']);
        }

        if (!empty($infoArr['contact']) && is_array($infoArr['contact'])) {
            $contact = ContactBuilder::create();
            if (!empty($infoArr['contact']['name'])) {
                $contact = $contact->name($infoArr['contact']['name']);
            }
            if (!empty($infoArr['contact']['url'])) {
                $contact = $contact->url($infoArr['contact']['url']);
            }
            if (!empty($infoArr['contact']['email'])) {
                $contact = $contact->email($infoArr['contact']['email']);
            }
            $infoBuilder = $infoBuilder->contact($contact);
        }

        if (!empty($infoArr['license']) && is_array($infoArr['license'])) {
            $license = LicenseBuilder::create()
                ->name($infoArr['license']['name'] ?? '');
            if (!empty($infoArr['license']['url'])) {
                $license = $license->url($infoArr['license']['url']);
            }
            $infoBuilder = $infoBuilder->license($license);
        }

        // OpenAPI root
        $openapiBuilder = OpenApiBuilder::create();
        $openapiBuilder = $openapiBuilder->openapi(ProfileProvider::profile()->majorMinor() . '.' . ProfileProvider::profile()->patch());
        $openapiBuilder = $openapiBuilder->info($infoBuilder);
        $openapiBuilder = $openapiBuilder->paths($assembled->paths);
        $openapiBuilder = $openapiBuilder->components($assembled->components);

        // Servers
        foreach ($assembled->servers as $srv) {
            $serverBuilder = Server::create()->url($srv['url'] ?? '');
            if (!empty($srv['description'])) {
                $serverBuilder = $serverBuilder->description($srv['description']);
            }
            $openapiBuilder = $openapiBuilder->addServer($serverBuilder);
        }
        // Tags
        foreach ($assembled->used_tags as $tagName) {
            $openapiBuilder = $openapiBuilder->addTag(Tag::of($tagName));
        }

        if (!empty($assembled->security)) {
            // примени все требования разом
            $openapiBuilder = $openapiBuilder->security(...$assembled->security);
        }

        // Extensions
        foreach ($assembled->extensions as $extName => $extVal) {
            $openapiBuilder = $openapiBuilder->extension($extName, $extVal);
        }



        return $openapiBuilder->toModel();
    }
}
