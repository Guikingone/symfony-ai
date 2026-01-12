<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Evaluator\Scorer;

use Symfony\AI\Evaluator\ScorerInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ToolCallScorer implements ScorerInterface
{
    public function __construct(
        private readonly array $toolCalls,
    ) {
    }

    public function score(DeferredResult $deferredResult, array $options = []): float
    {
        $toolsCalls = $deferredResult->asToolCalls();

        if ([] === $toolsCalls) {
            return 0.0;
        }

        $calledTools = array_diff($this->toolCalls, array_map(
            static fn (ToolCallResult $toolCallResult): array => array_map(
                static fn (ToolCall $toolCall): string => $toolCall->getName(),
                $toolCallResult->getContent(),
            ),
            $toolsCalls
        ));
    }
}
