<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Tests\Workflow;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\Workflow\WorkflowError;
use Symfony\AI\Agent\Workflow\WorkflowState;
use Symfony\AI\Agent\Workflow\WorkflowStatus;

final class WorkflowStateTest extends TestCase
{
    public function testConstruct(): void
    {
        $state = new WorkflowState('test-123');

        $this->assertSame('test-123', $state->getId());
        $this->assertSame(WorkflowStatus::PENDING, $state->getStatus());
        $this->assertSame([], $state->getContext());
        $this->assertSame([], $state->getMetadata());
    }

    public function testGetCurrentStepFromMarking(): void
    {
        $state = new WorkflowState('test-123', ['__marking' => ['processing' => 1]]);

        $this->assertSame('processing', $state->getCurrentStep());
    }

    public function testGetCurrentStepEmpty(): void
    {
        $state = new WorkflowState('test-123');

        $this->assertSame('', $state->getCurrentStep());
    }

    public function testMergeContext(): void
    {
        $state = new WorkflowState('test-123', ['key1' => 'value1']);
        $state->mergeContext(['key2' => 'value2']);

        $context = $state->getContext();
        $this->assertSame('value1', $context['key1']);
        $this->assertSame('value2', $context['key2']);
    }

    public function testAddError(): void
    {
        $state = new WorkflowState('test-123');
        $error = new WorkflowError('Test error', 'processing');

        $state->addError($error);

        $this->assertCount(1, $state->getErrors());
        $this->assertSame($error, $state->getErrors()[0]);
    }

    public function testClearErrors(): void
    {
        $state = new WorkflowState('test-123');
        $state->addError(new WorkflowError('Error 1', 'step1'));
        $state->addError(new WorkflowError('Error 2', 'step2'));

        $this->assertCount(2, $state->getErrors());

        $state->clearErrors();

        $this->assertCount(0, $state->getErrors());
    }

    public function testToArrayAndFromArray(): void
    {
        $state = new WorkflowState(
            'test-123',
            ['key' => 'value', '__marking' => ['processing' => 1]],
            ['meta' => 'data'],
            WorkflowStatus::RUNNING
        );
        $state->addError(new WorkflowError('Test error', 'processing'));

        $array = $state->toArray();

        $this->assertSame('test-123', $array['id']);
        $this->assertSame(['key' => 'value', '__marking' => ['processing' => 1]], $array['context']);
        $this->assertSame(['meta' => 'data'], $array['metadata']);
        $this->assertSame('running', $array['status']);
        $this->assertCount(1, $array['errors']);

        $restoredState = WorkflowState::fromArray($array);

        $this->assertSame('test-123', $restoredState->getId());
        $this->assertSame(WorkflowStatus::RUNNING, $restoredState->getStatus());
        $this->assertSame('processing', $restoredState->getCurrentStep());
        $this->assertCount(1, $restoredState->getErrors());
    }
}
