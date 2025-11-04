<?php

declare(strict_types=1);

namespace On1kel\HyperfFlyDocs\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use On1kel\HyperfFlyDocs\Generator\Cache\DocsCacheManager;

#[Listener]
final class GenerateDocsOnWorkerStartListener implements ListenerInterface
{
    public function __construct(
        private readonly ConfigInterface  $config,
        private readonly DocsCacheManager $cache
    ) {
    }

    public function listen(): array
    {
        return [
            AfterWorkerStart::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof AfterWorkerStart) {
            return;
        }

        /** @var string|null $config_mode */
        $config_mode = $this->config->get('fly-docs.cache.mode');

        // Режим автогенерации при старте
        $mode = $config_mode ?? 'lazy';
        if ($mode !== 'boot') {
            return;
        }

        // Генерируем только в одном воркере, чтобы избежать гонок
        /** @var AfterWorkerStart $event */
        if ((int)$event->workerId !== 0) {
            return;
        }

        $this->cache->generateAll();
    }
}
