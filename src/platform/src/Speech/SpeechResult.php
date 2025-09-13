<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Speech;

use Symfony\AI\Platform\Metadata\Metadata;
use Symfony\AI\Platform\Result\BaseResult;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\ResultInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class SpeechResult extends BaseResult
{
    public function __construct(
        private readonly ResultInterface $textResult,
        private readonly DeferredResult $speechResult,
    ) {
    }

    public function getContent(): string
    {
        return $this->textResult->getContent();
    }

    public function getTextResult(): ResultInterface
    {
        return $this->textResult;
    }

    public function asBinary(): string
    {
        return $this->speechResult->asBinary();
    }

    public function asDataUri(?string $mimeType = null): string
    {
        return $this->speechResult->asDataUri($mimeType);
    }

    public function asFile(string $path): void
    {
        $this->speechResult->asFile($path);
    }

    public function getMetadata(): Metadata
    {
        $metadata = parent::getMetadata();

        $metadata->merge($this->textResult->getMetadata());
        $metadata->merge($this->speechResult->getMetadata());

        return $metadata;
    }
}
