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
 * Represents an action to execute when entering a workflow place.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
interface ActionInterface
{
    public function execute(AgentInterface $agent, WorkflowStateInterface $state): ResultInterface;

    public function getName(): string;

    public function getRetryCount(): int;

    public function getRetryDelay(): int;
}
