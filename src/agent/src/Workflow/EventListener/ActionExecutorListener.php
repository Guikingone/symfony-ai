<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Workflow\Action\ActionInterface;
use Symfony\AI\Agent\Workflow\Action\ActionRunner;
use Symfony\AI\Agent\Workflow\WorkflowError;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class ActionExecutorListener implements EventSubscriberInterface
{
    /**
     * @param array<string, ActionInterface[]> $placeActions
     */
    public function __construct(
        private readonly array $placeActions,
        private readonly ActionRunner $actionRunner,
        private readonly AgentInterface $agent,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.entered' => 'onEntered',
        ];
    }

    public function onEntered(EnteredEvent $event): void
    {
        $subject = $event->getSubject();

        if (!$subject instanceof WorkflowStateInterface) {
            return;
        }

        $place = $event->getTransition()?->getTos()[0] ?? null;

        if (null === $place || !isset($this->placeActions[$place])) {
            return;
        }

        $actions = $this->placeActions[$place];

        if ([] === $actions) {
            return;
        }

        $this->logger->info('Executing actions for place', [
            'place' => $place,
            'action_count' => \count($actions),
            'workflow' => $subject->getId(),
        ]);

        foreach ($actions as $action) {
            try {
                $result = $this->actionRunner->run($action, $this->agent, $subject);

                $subject->mergeContext([
                    'last_result' => $result->getContent(),
                    'last_action' => $action->getName(),
                    'last_place' => $place,
                ]);

                $this->logger->debug('Action executed successfully', [
                    'action' => $action->getName(),
                    'place' => $place,
                ]);
            } catch (\Throwable $e) {
                $error = new WorkflowError(
                    $e->getMessage(),
                    $place,
                    $e->getCode(),
                    $e,
                    context: [
                        'action' => $action->getName(),
                        'trace' => $e->getTraceAsString(),
                    ],
                );

                $subject->addError($error);

                $this->logger->error('Action execution failed', [
                    'action' => $action->getName(),
                    'place' => $place,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }
}
