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

use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface;
use Symfony\AI\Platform\Result\RawHttpResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class BifrostClient implements ModelClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function supports(Model $model): bool
    {
        return $model instanceof Bifrost;
    }

    public function request(Model $model, array|string $payload, array $options = []): RawResultInterface
    {
        $payload = new BifrostPayload($payload);

        return match (true) {
            $model->supports(Capability::EMBEDDINGS) => $this->doGenerateEmbeddings($model, $payload, $options),
        };
    }

    private function doGenerateEmbeddings(Model $model, BifrostPayload $payload, array $options): RawResultInterface
    {
        return new RawHttpResult($this->httpClient->request('POST', 'embeddings', [
            'json' => [
                ...$options,
                'model' => $model->getName(),
                'input' => $payload->asEmbeddingsInput(),
            ],
        ]));
    }
}
