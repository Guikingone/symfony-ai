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
use Symfony\AI\Agent\Workflow\Action\Action;
use Symfony\AI\Agent\Workflow\WorkflowBuilder;
use Symfony\Component\Workflow\StateMachine;

final class WorkflowBuilderTest extends TestCase
{
    public function testCreateWorkflow(): void
    {
        $config = WorkflowBuilder::create('test-workflow')
            ->addPlace('start')
            ->addPlace('processing')
            ->addPlace('end')
            ->addTransition('begin', 'start', 'processing')
            ->addTransition('complete', 'processing', 'end')
            ->setInitialPlace('start')
            ->build();

        $this->assertArrayHasKey('stateMachine', $config);
        $this->assertArrayHasKey('definition', $config);
        $this->assertArrayHasKey('placeActions', $config);
        $this->assertArrayHasKey('transitionGuards', $config);
        $this->assertArrayHasKey('name', $config);

        $this->assertInstanceOf(StateMachine::class, $config['stateMachine']);
        $this->assertSame('test-workflow', $config['name']);
    }

    public function testAddActionForPlace(): void
    {
        $action = new Action('test-action', static fn (): null => null);

        $config = WorkflowBuilder::create('test-workflow')
            ->addPlace('start')
            ->addActionForPlace('start', $action)
            ->build();

        $this->assertArrayHasKey('start', $config['placeActions']);
        $this->assertContains($action, $config['placeActions']['start']);
    }

    public function testAddGuardForTransition(): void
    {
        $guard = static fn (): bool => true;

        $config = WorkflowBuilder::create('test-workflow')
            ->addPlace('start')
            ->addPlace('end')
            ->addTransition('go', 'start', 'end')
            ->addGuardForTransition('go', $guard)
            ->build();

        $this->assertArrayHasKey('go', $config['transitionGuards']);
        $this->assertContains($guard, $config['transitionGuards']['go']);
    }

    public function testDefaultInitialPlace(): void
    {
        $config = WorkflowBuilder::create('test-workflow')
            ->addPlace('first')
            ->addPlace('second')
            ->build();

        $definition = $config['definition'];
        $this->assertSame('first', $definition->getInitialPlaces()[0]);
    }
}
