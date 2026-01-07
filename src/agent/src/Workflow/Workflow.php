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

use Symfony\Component\Workflow\Workflow as SymfonyWorkflow;
use Symfony\AI\Platform\Result\ResultInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class Workflow
{
    /**
     * @var WorkflowStepInterface
     */
    private array $steps = [];

    public function __construct(
        private readonly string                    $name,
        private readonly StoreInterface            $store,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
    ) {
    }

    public function addStep(WorkflowStepInterface $step): self
    {
        $this->steps[$step->getName()] = $step;

        $this->eventDispatcher?->dispatch(new WorkflowStepAddedEvent($step));

        return $this;
    }

    public function call(array $options = []): ResultInterface
    {
        $workflow = $this->build();

        $state = new WorkflowState(WorkflowStateEnum::STARTED);

        $this->eventDispatcher?->dispatch(new WorkflowStartedEvent($state));

        foreach ($this->steps as $step) {
            try {
                $transitionName = 'to_' . $step->getName();

                if ($workflow->can($state, $transitionName)) {
                    $workflow->apply($state, $transitionName);
                    $step->execute($state);

                    $this->eventDispatcher?->dispatch(new WorkflowStepExecutedEvent($state, $step));

                    $this->store->save($state);
                }
            } catch (\Throwable $throwable) {
                $this->eventDispatcher?->dispatch(new WorkflowFailedEvent($state, $step));
            }
        }

        if ($workflow->can($state, WorkflowStateEnum::FINISHED->value)) {
            $workflow->apply($state, WorkflowStateEnum::FINISHED->value);

            $this->store->save($state);
        }

        $this->eventDispatcher?->dispatch(new WorkflowFinishedEvent($state));

        $result = $state->getResult();
        if (!$result instanceof ResultInterface) {
            throw new RuntimeException('No result set during execution.');
        }

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function build(): SymfonyWorkflow
    {
        if ([] === $this->steps) {
            throw new RuntimeException('Workflow must at least define a step.');
        }

        $definitionBuilder = new DefinitionBuilder();
        $stepNames = array_keys($this->steps);
    }
}
