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

use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\ResultConverterInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageExtractorInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class BifrostResultConverter implements ResultConverterInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof Bifrost;
    }

    public function convert(RawResultInterface $result, array $options = []): ResultInterface
    {
        // TODO: Implement convert() method.
    }

    public function getTokenUsageExtractor(): TokenUsageExtractorInterface
    {
        return new BifrostTokenUsageExtractor();
    }
}
