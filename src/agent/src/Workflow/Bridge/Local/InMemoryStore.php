<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Bridge\Local;

use Symfony\AI\Agent\Workflow\ManagedStoreInterface;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Agent\Workflow\StoreInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class InMemoryStore implements ManagedStoreInterface, StoreInterface
{
    /**
     * @var WorkflowStateInterface[]
     */
    private array $workflowStates = [];

    public function setup(array $options = []): void
    {
        if ([] !== $options) {
            throw new InvalidArgumentException(\sprintf('No supported options.'));
        }

        $this->workflowStates = [];
    }

    public function drop(): void
    {
        $this->workflowStates = [];
    }

    public function save(WorkflowStateInterface $state): void
    {
        $this->workflowStates[$state->getAgent()->getName()] = $state;
    }

    public function load(string $name): WorkflowStateInterface
    {
        return $this->workflowStates[$name] ?? throw new InvalidArgumentException(\sptintf('No workflow "%s" found.', $name));
    }
}
