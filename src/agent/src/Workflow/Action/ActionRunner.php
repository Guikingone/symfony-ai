<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Action;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Exception\RuntimeException;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MonotonicClock;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ActionRunner
{
    public function __construct(
        private readonly ClockInterface $clock = new MonotonicClock(),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @throws RuntimeException If the action fails after all retry attempts
     */
    public function run(ActionInterface $action, AgentInterface $agent, WorkflowStateInterface $state): ResultInterface
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $action->getRetryCount()) {
            try {
                $this->logger->debug('Executing action', [
                    'action' => $action->getName(),
                    'attempt' => $attempt + 1,
                    'max_attempts' => $action->getRetryCount(),
                ]);

                return $action->execute($agent, $state);
            } catch (\Throwable $e) {
                $lastException = $e;
                ++$attempt;

                if ($attempt < $action->getRetryCount()) {
                    $this->logger->warning('Action failed, retrying', [
                        'action' => $action->getName(),
                        'attempt' => $attempt,
                        'max_attempts' => $action->getRetryCount(),
                        'error' => $e->getMessage(),
                    ]);

                    $this->clock->sleep($action->getRetryDelay());
                }
            }
        }

        $this->logger->error('Action failed after all retry attempts', [
            'action' => $action->getName(),
            'attempts' => $action->getRetryCount(),
            'error' => $lastException?->getMessage(),
        ]);

        throw new RuntimeException(\sprintf('Action "%s" failed after %d attempts', $action->getName(), $action->getRetryCount()), previous: $lastException);
    }
}
