<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Event;

use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Agent\Workflow\WorkflowStepInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowFailedEvent
{
    public function __construct(
        private readonly WorkflowStateInterface $state,
        private readonly WorkflowStepInterface $step,
    ) {
    }

    public function getState(): WorkflowStateInterface
    {
        return $this->state;
    }

    public function getStep(): WorkflowStepInterface
    {
        return $this->step;
    }
}
