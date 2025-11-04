<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Http\Controller;

use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Khazhinov\HyperfFlyDocs\Generator\Cache\DocsCacheManager;
use Psr\Http\Message\ResponseInterface;

#[Controller(prefix: 'fly-docs')]
final class DocsController
{
    public function __construct(
        private readonly HttpResponse     $response,
        private readonly DocsCacheManager $cache,
        private readonly ConfigInterface  $config,
    ) {
    }

    #[GetMapping(path: '')]
    public function root(): ResponseInterface
    {
        $defaultVal = $this->config->get('fly-docs.default_collection');
        $default = is_string($defaultVal) ? $defaultVal : 'latest';

        return $this->response->redirect('/fly-docs/' . $default, 302);
    }

    /**
     * @param  string            $tag
     * @return ResponseInterface
     */
    #[GetMapping(path: '{tag}/api-docs')]
    public function json(string $tag): ResponseInterface
    {
        $this->cache->ensure($tag);

        /** @var array<string|int, mixed>|null $doc */
        $doc = $this->cache->read($tag);
        if ($doc === null) {
            return $this->response
                ->json(['message' => 'Коллекция не найдена: ' . $tag])
                ->withStatus(404);
        }

        return $this->response->json($doc);
    }

    #[GetMapping(path: '{tag}')]
    public function index(string $tag): ResponseInterface
    {
        $uiVal = $this->config->get('fly-docs.ui', []);
        $ui = is_array($uiVal) ? $uiVal : [];

        $titleVal = $ui['index_title'] ?? 'API Docs';
        $title = is_string($titleVal) ? $titleVal : 'API Docs';

        $useCdnVal = $ui['use_cdn'] ?? true;
        $useCdn = is_bool($useCdnVal) ? $useCdnVal : true;

        // Локальный шаблон, если публикуешь ассеты
        $index = base_path('/publish/fly-docs/index.html');
        if (is_file($index)) {
            $html = file_get_contents($index);
            if ($html === false) {
                // Не удалось прочитать файл — отдадим 500
                return $this->response->raw('')->withStatus(500);
            }

            $html = str_replace('__SPEC_URL__', '/fly-docs/' . rawurlencode($tag) . '/api-docs', $html);
            return $this->response->html($html);
        }

        $specUrl = '/fly-docs/' . rawurlencode($tag) . '/api-docs';

        // Если нужен CDN — переключим конфиг (по ситуации)
        if ($useCdn !== true) {
            $this->config->set('fly-docs.ui.use_cdn', true);
        }

        $html = <<<HTML
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>{$title}</title>
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/dist/swagger-ui.css" />
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5/dist/swagger-ui-bundle.js"></script>
  <script>
    window.ui = SwaggerUIBundle({ url: "{$specUrl}", dom_id: '#swagger-ui' });
  </script>
</body>
</html>
HTML;

        return $this->response->html($html);
    }

    #[GetMapping(path: '/fly-docs-assets/{path:.+}')]
    public function assets(string $path): ResponseInterface
    {
        // БЕЗОПАСНОСТЬ: режем выход наверх
        $path = str_replace(['..', '\\'], ['', '/'], $path);

        $base = base_path('/publish/fly-docs/index_files');
        $file = $base . '/' . $path;

        if (!is_file($file)) {
            return $this->response->raw('')->withStatus(404);
        }

        // контент-тайпы по расширению
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'map' => 'application/json; charset=utf-8',
            default => 'application/octet-stream',
        };

        $content = file_get_contents($file);
        if ($content === false) {
            return $this->response->raw('')->withStatus(500);
        }

        // Сначала получаем PSR-ответ, затем навешиваем заголовок
        return $this->response
            ->raw($content)
            ->withHeader('Content-Type', $mime);
    }
}
