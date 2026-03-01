<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\MarkingStore;

use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

/**
 * Custom marking store for WorkflowState.
 *
 * This stores the current place(s) in the WorkflowState's context
 * instead of using a property, allowing full control over persistence.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class StateMarkingStore implements MarkingStoreInterface
{
    private const MARKING_KEY = '__marking';

    public function __construct(
        private readonly bool $singleState = true,
    ) {
    }

    public function getMarking(object $subject): Marking
    {
        if (!$subject instanceof WorkflowStateInterface) {
            throw new \InvalidArgumentException(\sprintf('Subject must be an instance of %s, %s given', WorkflowStateInterface::class, get_debug_type($subject)));
        }

        $context = $subject->getContext();
        $places = $context[self::MARKING_KEY] ?? [];

        if (!\is_array($places)) {
            $places = [];
        }

        return new Marking($places);
    }

    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        if (!$subject instanceof WorkflowStateInterface) {
            throw new \InvalidArgumentException(\sprintf('Subject must be an instance of %s, %s given', WorkflowStateInterface::class, get_debug_type($subject)));
        }

        $places = $marking->getPlaces();

        $subject->mergeContext([
            self::MARKING_KEY => $places,
        ]);
    }
}
