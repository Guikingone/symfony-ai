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

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Platform\Result\ResultInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class Action implements ActionInterface
{
    /**
     * @param \Closure(AgentInterface, WorkflowStateInterface): ResultInterface $executor
     */
    public function __construct(
        private readonly string $name,
        private readonly \Closure $executor,
        private readonly int $retryCount = 3,
        private readonly int $retryDelay = 1,
    ) {
    }

    public function execute(AgentInterface $agent, WorkflowStateInterface $state): ResultInterface
    {
        return ($this->executor)($agent, $state);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
}
