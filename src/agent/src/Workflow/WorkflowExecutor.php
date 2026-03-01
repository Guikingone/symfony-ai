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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Exception\RuntimeException;
use Symfony\AI\Agent\Workflow\Action\ActionInterface;
use Symfony\AI\Agent\Workflow\Event\WorkflowCompletedEvent;
use Symfony\AI\Agent\Workflow\Event\WorkflowFailedEvent;
use Symfony\AI\Agent\Workflow\Event\WorkflowStartedEvent;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MonotonicClock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowExecutor implements WorkflowExecutorInterface
{
    private readonly StateMachine $stateMachine;

    /**
     * @param array{
     *     definition: Definition,
     *     stateMachine: StateMachine,
     *     placeActions: array<string, ActionInterface[]>,
     *     transitionGuards: array<string, array<\Closure>>,
     *     name: string
     * } $workflowConfig
     */
    public function __construct(
        private readonly array $workflowConfig,
        private readonly WorkflowStoreInterface $store,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly int $maxExecutionTime = 300,
        private readonly ClockInterface $clock = new MonotonicClock(),
    ) {
        $this->stateMachine = $workflowConfig['stateMachine'];
    }

    public function execute(AgentInterface $agent, WorkflowStateInterface $state, array $options = []): ResultInterface
    {
        $startTime = $this->clock->now();

        try {
            $state->setStatus(WorkflowStatus::RUNNING);
            $this->store->save($state);

            $this->eventDispatcher?->dispatch(new WorkflowStartedEvent($state));
            $this->logger->info('Workflow started', [
                'id' => $state->getId(),
                'workflow' => $this->workflowConfig['name'],
            ]);

            $result = $this->executeWorkflow($state, $startTime);

            $state->setStatus(WorkflowStatus::COMPLETED);
            $this->store->save($state);

            $this->eventDispatcher?->dispatch(new WorkflowCompletedEvent($state));
            $this->logger->info('Workflow completed', ['id' => $state->getId()]);

            return $result;
        } catch (\Throwable $e) {
            $this->handleError($state, $e);

            throw $e;
        }
    }

    public function resume(string $id, AgentInterface $agent): ResultInterface
    {
        $state = $this->store->load($id);

        if (!$state instanceof WorkflowStateInterface) {
            throw new RuntimeException(\sprintf('Workflow with ID "%s" not found.', $id));
        }

        if (WorkflowStatus::COMPLETED === $state->getStatus()) {
            throw new RuntimeException(\sprintf('Workflow "%s" is already completed.', $id));
        }

        if (WorkflowStatus::CANCELLED === $state->getStatus()) {
            throw new RuntimeException(\sprintf('Workflow "%s" has been cancelled.', $id));
        }

        if (WorkflowStatus::FAILED === $state->getStatus()) {
            $state->clearErrors();
            $this->logger->info('Cleared errors for failed workflow', ['id' => $id]);
        }

        $state->setStatus(WorkflowStatus::RUNNING);
        $this->store->save($state);

        $this->logger->info('Resuming workflow', [
            'id' => $id,
            'current_place' => $state->getCurrentStep(),
        ]);

        $resumeTime = $this->clock->now();

        try {
            $result = $this->executeWorkflow($state, $resumeTime);

            $state->setStatus(WorkflowStatus::COMPLETED);
            $this->store->save($state);

            $this->eventDispatcher?->dispatch(new WorkflowCompletedEvent($state));
            $this->logger->info('Workflow resumed and completed', ['id' => $id]);

            return $result;
        } catch (\Throwable $e) {
            $this->handleError($state, $e);

            throw $e;
        }
    }

    private function executeWorkflow(WorkflowStateInterface $state, \DateTimeImmutable $startTime): ResultInterface
    {
        $lastResult = null;
        $maxIterations = 100; // Prevent infinite loops
        $iteration = 0;

        while ($iteration < $maxIterations) {
            if (($this->clock->now()->getTimestamp() - $startTime->getTimestamp()) > $this->maxExecutionTime) {
                throw new \RuntimeException(\sprintf('Workflow execution exceeded maximum time of %d seconds.', $this->maxExecutionTime));
            }

            ++$iteration;

            $currentPlace = $state->getCurrentStep();

            if ('' === $currentPlace) {
                throw new RuntimeException('Workflow has no current place');
            }

            $this->logger->debug('Current workflow place', [
                'place' => $currentPlace,
                'iteration' => $iteration,
            ]);

            $enabledTransitions = array_filter(
                $this->stateMachine->getDefinition()->getTransitions(),
                fn (Transition $transition): bool => $this->stateMachine->can($state, $transition->getName())
            );

            if ([] === $enabledTransitions) {
                $this->logger->debug('No more transitions available, workflow complete');
                break;
            }

            $transition = reset($enabledTransitions);

            try {
                $this->logger->debug('Applying transition', [
                    'transition' => $transition->getName(),
                    'from' => $transition->getFroms(),
                    'to' => $transition->getTos(),
                ]);

                $this->stateMachine->apply($state, $transition->getName());

                $this->store->save($state);

                if (isset($state->getContext()['last_result'])) {
                    $lastResult = $state->getContext()['last_result'];
                }
            } catch (\Throwable $e) {
                $error = new WorkflowError($e->getMessage(), $currentPlace, $e->getCode(), $e, context: [
                    'transition' => $transition->getName(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $state->addError($error);
                $this->store->save($state);

                throw $e;
            }
        }

        if ($iteration >= $maxIterations) {
            throw new RuntimeException('Workflow exceeded maximum iterations');
        }

        return $lastResult ?? new TextResult('Workflow completed successfully');
    }

    private function handleError(WorkflowStateInterface $state, \Throwable $e): void
    {
        $error = new WorkflowError($e->getMessage(), $state->getCurrentStep(), $e->getCode(), $e, context: [
            'trace' => $e->getTraceAsString(),
        ]);

        $state->addError($error);
        $state->setStatus(WorkflowStatus::FAILED);
        $this->store->save($state);

        $this->eventDispatcher?->dispatch(new WorkflowFailedEvent($state, $e));
        $this->logger->error('Workflow failed', [
            'id' => $state->getId(),
            'error' => $e->getMessage(),
        ]);
    }
}
