<?php

declare(strict_types=1);

namespace Khazhinov\HyperfFlyDocs\Generator\Cache;

use function bin2hex;
use function file_get_contents;
use function file_put_contents;

use Hyperf\Contract\ConfigInterface;

use function is_dir;
use function is_file;
use function json_decode;

use Khazhinov\HyperfFlyDocs\Generator\Assembler\CollectionAssembler;
use Khazhinov\HyperfFlyDocs\Generator\DTO\ConfigDTO;
use Khazhinov\HyperfFlyDocs\Generator\Services\DocumentFactory;

use function mkdir;

use On1kel\OAS\Builder\Support\ProfileProvider;
use On1kel\OAS\Core\Model\OpenApiDocument;
use On1kel\OAS\Core\Serialize\DefaultDenormalizer;
use On1kel\OAS\Core\Serialize\DefaultNormalizer;
use On1kel\OAS\Core\Serialize\DefaultSerializer;
use On1kel\OAS\Core\Serialize\TypeRegistry;
use On1kel\OAS\Profile31\Profile\OAS31Profile;

use function random_bytes;
use function rename;

use RuntimeException;

use function unlink;

final class DocsCacheManager
{
    private string $cache_dir;

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly CollectionAssembler $assembler,
        private readonly DocumentFactory $document_factory,
    ) {
        /** @var string $config_dir */
        $config_dir = $this->config->get('fly-docs.cache.dir', '/runtime/fly-docs');
        $this->cache_dir = base_path($config_dir);
    }

    public function generateAll(): void
    {
        $rawCollections = $this->config->get('fly-docs.collections') ?? [];
        if (!is_array($rawCollections)) {
            return;
        }

        foreach ($rawCollections as $tag => $rawCfg) {
            $this->generateOne($tag, $rawCfg);
        }
    }

    public function generateOne(string $tag, array $rawCfg): void
    {

        ProfileProvider::setDefault(new OAS31Profile());
        $cfg = new ConfigDTO($rawCfg);

        $assembled = $this->assembler->assemble($cfg);


        $doc = $this->document_factory->make($assembled);


        $json = $this->serializeToJson($doc);


        $this->persistJson($tag, $json);
    }

    public function ensure(string $tag): void
    {
        $path = $this->jsonPath($tag);
        if (is_file($path)) {
            return;
        }

        $rawCollections = $this->config->get('fly-docs.collections') ?? [];
        $rawCfg = $rawCollections[$tag] ?? null;
        if (!is_array($rawCfg)) {
            throw new RuntimeException("Коллекция '{$tag}' не найдена в конфиге");
        }

        $this->generateOne($tag, $rawCfg);
    }

    private function serializeToJson(OpenApiDocument $doc): string
    {
        $serializer = new DefaultSerializer(
            normalizers: [new DefaultNormalizer()],
            denormalizer: new DefaultDenormalizer(
                new TypeRegistry(),
            ),
        );

        return $serializer->toJson($doc, ProfileProvider::profile());
    }

    private function persistJson(string $tag, string $json): void
    {
        $dir = $this->cache_dir;
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new RuntimeException("Cannot create cache dir: {$dir}");
        }

        $path = $dir . '/' . $this->safeTag($tag) . '.json';

        $tmp = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (file_put_contents($tmp, $json) === false) {
            throw new RuntimeException("Unable to write temp file: {$tmp}");
        }

        if (!rename($tmp, $path)) {
            unlink($tmp);
            throw new RuntimeException("Unable to move temp file to: {$path}");
        }
    }

    /**
     * @param  string $tag
     * @return mixed
     */
    public function read(string $tag): mixed
    {
        $this->ensure($tag);

        $path = $this->jsonPath($tag);

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Не удалось прочитать кэш: {$path}");
        }

        return json_decode($content, true);
    }

    public function jsonPath(string $tag): string
    {
        return $this->cache_dir . '/' . $this->safeTag($tag) . '.json';
    }

    private function safeTag(string $tag): string
    {
        return preg_replace('~[^a-zA-Z0-9_\-\.]~', '_', $tag) ?? $tag;
    }
}
