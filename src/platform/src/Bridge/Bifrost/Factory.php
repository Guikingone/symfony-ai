<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Bifrost;

use Symfony\AI\Platform\Bridge\Bifrost\Contract\BifrostContract;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\ModelRouter\CatalogBasedModelRouter;
use Symfony\AI\Platform\ModelRouterInterface;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Provider;
use Symfony\AI\Platform\ProviderInterface;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class Factory
{
    public static function createProvider(
        ?string $endpoint = null,
        #[\SensitiveParameter] ?string $apiKey = null,
        ?HttpClientInterface $httpClient = null,
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'bifrost',
    ): ProviderInterface {
        $httpClient = $httpClient instanceof EventSourceHttpClient ? $httpClient : new EventSourceHttpClient($httpClient);

        if (null !== $apiKey) {
            $httpClient = ScopingHttpClient::forBaseUri($httpClient, $endpoint, [
                'auth_bearer' => $apiKey,
            ]);
        }

        return new Provider(
            $name,
            [new BifrostClient($httpClient)],
            [new BifrostResultConverter()],
            new ModelCatalog($httpClient),
            $contract ?? BifrostContract::create(),
            $eventDispatcher,
        );
    }

    /**
     * @param non-empty-string $name
     */
    public static function createPlatform(
        ?string $endpoint = null,
        #[\SensitiveParameter] ?string $apiKey = null,
        ?HttpClientInterface $httpClient = null,
        ?Contract $contract = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        string $name = 'bifrost',
        ?ModelRouterInterface $modelRouter = null,
    ): Platform {
        return new Platform(
            [self::createProvider($endpoint, $apiKey, $httpClient, $contract, $eventDispatcher, $name)],
            $modelRouter ?? new CatalogBasedModelRouter(),
            $eventDispatcher,
        );
    }
}
