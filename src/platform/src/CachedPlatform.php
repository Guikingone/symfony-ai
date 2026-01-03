<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform;

use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MonotonicClock;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class CachedPlatform implements PlatformInterface
{
    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly ClockInterface $clock = new MonotonicClock(),
        private readonly (CacheInterface&TagAwareAdapterInterface)|null $cache = null,
        private readonly ?string $cacheKey = null,
        private readonly ?int $cacheTtl = null,
    ) {
    }

    public function invoke(string $model, array|string|object $input, array $options = []): DeferredResult
    {
        if (null === $this->cache || !\array_key_exists('prompt_cache_key', $options) || '' === $options['prompt_cache_key']) {
            return $this->platform->invoke($model, $input, $options);
        }

        $cacheKey = \sprintf('%s_%s_%s', $this->cacheKey ?? $options['prompt_cache_key'], md5($model), \is_string($input) ? md5($input) : md5(json_encode($input)));
        $ttl = $options['prompt_cache_ttl'] ?? $this->cacheTtl;

        unset($options['prompt_cache_key'], $options['prompt_cache_ttl']);

        $cached = $this->cache->get($cacheKey, function (ItemInterface $item) use ($model, $input, $options, $cacheKey, $ttl): array {
            $item->tag((new UnicodeString($model))->camel());

            if (null !== $ttl) {
                $item->expiresAfter($ttl);
            }

            $deferredResult = $this->platform->invoke($model, $input, $options);

            $result = $deferredResult->getResult();

            $result->getMetadata()->set([
                'cached' => true,
                'cache_key' => $cacheKey,
                'cached_at' => $this->clock->now()->getTimestamp(),
            ]);

            return [
                'result' => $result,
                'raw_data' => $deferredResult->getRawResult()->getData(),
            ];
        });

        $result = new DeferredResult(
            new CachedResultConverter($cached['result']),
            new InMemoryRawResult($cached['raw_data']),
            $options,
        );

        $result->getMetadata()->merge($cached['result']->getMetadata()->all());

        return $result;
    }

    public function getModelCatalog(): ModelCatalogInterface
    {
        return $this->platform->getModelCatalog();
    }
}
