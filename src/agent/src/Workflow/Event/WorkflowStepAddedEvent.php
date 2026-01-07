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

use Symfony\AI\Agent\Workflow\WorkflowStepInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowStepAddedEvent
{
    public function __construct(
        private readonly WorkflowStepInterface $step,
    ) {
    }
}
