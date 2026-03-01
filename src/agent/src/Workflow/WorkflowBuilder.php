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
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Workflow\Action\ActionInterface;
use Symfony\AI\Agent\Workflow\Action\ActionRunner;
use Symfony\AI\Agent\Workflow\EventListener\ActionExecutorListener;
use Symfony\AI\Agent\Workflow\EventListener\GuardListener;
use Symfony\AI\Agent\Workflow\MarkingStore\StateMarkingStore;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Metadata\InMemoryMetadataStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowBuilder
{
    /** @var string[] */
    private array $places = [];

    /** @var Transition[] */
    private array $transitions = [];

    /** @var array<string, ActionInterface[]> */
    private array $placeActions = [];

    /** @var array<string, array<\Closure>> */
    private array $transitionGuards = [];

    /** @var array<string, mixed> */
    private array $metadata = [];

    private ?string $initialPlace = null;

    private function __construct(
        private readonly string $name,
        private readonly ?AgentInterface $agent = null,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public static function create(string $name, ?AgentInterface $agent = null, ?EventDispatcherInterface $eventDispatcher = null, ?LoggerInterface $logger = null): self
    {
        return new self($name, $agent, $eventDispatcher, $logger);
    }

    public function addPlace(string $place, array $metadata = []): self
    {
        if (!\in_array($place, $this->places, true)) {
            $this->places[] = $place;

            if ([] !== $metadata) {
                $this->metadata['places'][$place] = $metadata;
            }
        }

        return $this;
    }

    public function setInitialPlace(string $place): self
    {
        $this->initialPlace = $place;
        $this->addPlace($place);

        return $this;
    }

    /**
     * @param string|string[] $from
     * @param string|string[] $to
     */
    public function addTransition(string $name, string|array $from, string|array $to, array $metadata = []): self
    {
        $from = [$from];
        $to = [$to];

        // Ensure all places exist
        foreach ([...$from, ...$to] as $place) {
            $this->addPlace($place);
        }

        $this->transitions[] = new Transition($name, $from, $to);

        if ([] !== $metadata) {
            $this->metadata['transitions'][$name] = $metadata;
        }

        return $this;
    }

    public function addActionForPlace(string $place, ActionInterface $action): self
    {
        $this->addPlace($place);
        $this->placeActions[$place] ??= [];
        $this->placeActions[$place][] = $action;

        return $this;
    }

    /**
     * @param \Closure(WorkflowStateInterface): bool $guard
     */
    public function addGuardForTransition(string $transitionName, \Closure $guard): self
    {
        $this->transitionGuards[$transitionName] ??= [];
        $this->transitionGuards[$transitionName][] = $guard;

        return $this;
    }

    public function build(): array
    {
        if (null === $this->initialPlace) {
            $this->initialPlace = reset($this->places);
        }

        $definition = new Definition(
            $this->places,
            $this->transitions,
            $this->initialPlace,
            new InMemoryMetadataStore($this->metadata)
        );

        $this->eventDispatcher?->addSubscriber(new GuardListener($this->transitionGuards, $this->logger));
        $this->eventDispatcher?->addSubscriber(
            new ActionExecutorListener(
                $this->placeActions,
                new ActionRunner(logger: $this->logger),
                $this->agent,
                $this->logger
            )
        );

        $markingStore = new StateMarkingStore(true);
        $stateMachine = new StateMachine($definition, $markingStore, $this->eventDispatcher, $this->name);

        return [
            'definition' => $definition,
            'stateMachine' => $stateMachine,
            'placeActions' => $this->placeActions,
            'transitionGuards' => $this->transitionGuards,
            'name' => $this->name,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }
}
