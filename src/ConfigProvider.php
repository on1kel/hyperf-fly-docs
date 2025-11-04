<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs;

use Khazhinov\HyperfFlyDocs\Generator\Assembler\CollectionAssembler;
use Khazhinov\HyperfFlyDocs\Generator\Cache\DocsCacheManager;
use Khazhinov\HyperfFlyDocs\Generator\Contracts\RouteExtractorInterface;
use Khazhinov\HyperfFlyDocs\Generator\Extractor\CollectorRouteExtractor;
use Khazhinov\HyperfFlyDocs\Generator\Registry\ComponentsRegistry;
use Khazhinov\HyperfFlyDocs\Generator\Services\ComplexRunner;
use Khazhinov\HyperfFlyDocs\Generator\Services\DocumentFactory;
use Khazhinov\HyperfFlyDocs\Generator\Services\OperationComposer;
use Khazhinov\HyperfFlyDocs\Generator\Services\OperationMetaResolver;
use Khazhinov\HyperfFlyDocs\Generator\Services\PathsAccumulator;
use Khazhinov\HyperfFlyDocs\Generator\Services\RouteFilter;
use Khazhinov\HyperfFlyDocs\Listener\GenerateDocsOnWorkerStartListener;

final class ConfigProvider
{
    public function __invoke(): array
    {
        $this->autoPublishConfig();

        return [
            'dependencies' => [
                RouteExtractorInterface::class => CollectorRouteExtractor::class,
                CollectionAssembler::class   => CollectionAssembler::class,
                DocsCacheManager::class      => DocsCacheManager::class,
                ComponentsRegistry::class    => ComponentsRegistry::class,
                RouteFilter::class           => RouteFilter::class,
                OperationMetaResolver::class => OperationMetaResolver::class,
                ComplexRunner::class         => ComplexRunner::class,
                OperationComposer::class     => OperationComposer::class,
                DocumentFactory::class       => DocumentFactory::class,
                PathsAccumulator::class      => PathsAccumulator::class,
            ],
            'listeners' => [
                GenerateDocsOnWorkerStartListener::class
            ],
            'commands' => [
                // при желании позже добавим команду генерации на CI
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__, // сканировать src пакета (включая Http\Controller\DocsController)
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'fly-docs config',
                    'source' => __DIR__ . '/../publish/config/fly-docs.php',
                    'destination' => \BASE_PATH . '/config/autoload/fly-docs.php',
                ],
                [
                    'id' => 'ui',
                    'description' => 'fly-docs UI assets (optional)',
                    'source' => __DIR__ . '/../publish/ui',
                    'destination' => \BASE_PATH . '/publish/fly-docs',
                ],
            ],
        ];
    }

    private function autoPublishConfig(): void
    {
        $src = __DIR__ . '/../publish/config/fly-docs.php';
        $dst = \BASE_PATH . '/config/autoload/fly-docs.php';

        if (! file_exists($dst)) {
            $dir = \dirname($dst);
            if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
                throw new \RuntimeException("Cannot create directory: {$dir}");
            }
            if (! copy($src, $dst)) {
                throw new \RuntimeException("Cannot copy {$src} to {$dst}");
            }
        }
    }
}
