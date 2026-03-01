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
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Evaluates guards for workflow transitions.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class GuardListener implements EventSubscriberInterface
{
    /**
     * @param array<string, array<\Closure>> $transitionGuards
     */
    public function __construct(
        private readonly array $transitionGuards,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.guard' => 'onGuard',
        ];
    }

    public function onGuard(GuardEvent $event): void
    {
        $subject = $event->getSubject();

        if (!$subject instanceof WorkflowStateInterface) {
            return;
        }

        $transitionName = $event->getTransition()->getName();

        if (!isset($this->transitionGuards[$transitionName])) {
            return;
        }

        $guards = $this->transitionGuards[$transitionName];

        foreach ($guards as $guard) {
            try {
                $result = $guard($subject);

                if (false === $result) {
                    $event->setBlocked(true);

                    $this->logger->debug('Transition blocked by guard', [
                        'transition' => $transitionName,
                        'workflow' => $subject->getId(),
                    ]);

                    return;
                }
            } catch (\Throwable $e) {
                $event->setBlocked(true);

                $this->logger->error('Guard evaluation failed', [
                    'transition' => $transitionName,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }
}
