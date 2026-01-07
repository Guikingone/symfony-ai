<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowStep implements WorkflowStepInterface
{
    public function __construct(
        private readonly string $name,
        private readonly AgentInterface $agent,
    ) {
    }

    public function execute(WorkflowStateInterface $state): void
    {
        $result = $this->agent->call($state->getMessageBag());

        $state->setResult($result);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
