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

final class BifrostPayload
{
    public function __construct(
        private readonly array|string $input,
    ) {
    }

    public function asEmbeddingsPayload(): string
    {
        if (\is_string($this->input)) {
            return $this->input;
        }
    }
}
