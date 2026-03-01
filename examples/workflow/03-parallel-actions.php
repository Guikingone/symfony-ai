<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Agent\MockAgent;
use Symfony\AI\Agent\Workflow\Action\Action;
use Symfony\AI\Agent\Workflow\Action\ActionRunner;
use Symfony\AI\Agent\Workflow\Store\InMemoryWorkflowStore;
use Symfony\AI\Agent\Workflow\WorkflowBuilder;
use Symfony\AI\Agent\Workflow\WorkflowExecutor;
use Symfony\AI\Agent\Workflow\WorkflowState;
use Symfony\AI\Platform\Result\Result;

require_once dirname(__DIR__).'/bootstrap.php';

echo "=== Parallel Actions Example ===\n\n";

if (!extension_loaded('fiber')) {
    echo "⚠ Fiber extension not loaded, parallel execution will fallback to sequential\n\n";
} else {
    echo "✓ Fiber extension loaded, parallel execution enabled\n\n";
}

$workflowConfig = WorkflowBuilder::create('batch-processor')
    ->addPlace('idle')
    ->addPlace('processing')
    ->addPlace('completed')
    ->setInitialPlace('idle')
    ->addTransition('start', 'idle', 'processing')
    ->addTransition('finish', 'processing', 'completed')

    // Multiple parallel actions on same place
    ->addActionForPlace('processing', new Action(
        'process-emails',
        static function ($agent, $state) {
            usleep(100000); // 100ms

            return new Result('Processed 500 emails');
        },
        parallel: true
    ))
    ->addActionForPlace('processing', new Action(
        'process-notifications',
        static function ($agent, $state) {
            usleep(100000); // 100ms

            return new Result('Sent 250 notifications');
        },
        parallel: true
    ))
    ->addActionForPlace('processing', new Action(
        'update-analytics',
        static function ($agent, $state) {
            usleep(100000); // 100ms

            return new Result('Updated analytics dashboard');
        },
        parallel: true
    ))

    ->build();

$state = new WorkflowState(
    'batch-001',
    context: [
        '__marking' => ['idle' => 1],
        'batch_id' => 'BATCH-2024-001',
    ]
);

$executor = new WorkflowExecutor(
    $workflowConfig,
    new InMemoryWorkflowStore(),
);

echo "Executing workflow with parallel actions...\n";
echo "  - Batch ID: {$state->getContext()['batch_id']}\n";
echo "  - Actions: 3 parallel tasks\n\n";

$startTime = microtime(true);

try {
    // Note: ActionExecutorListener executes actions sequentially
    // For true parallel execution, you would need to modify the listener
    // or handle it at the ActionRunner level
    $result = $executor->execute(new MockAgent(), $state);

    $duration = round((microtime(true) - $startTime) * 1000, 2);

    echo "✓ Workflow completed!\n";
    echo "  - Duration: {$duration}ms\n";
    echo "  - Final place: {$state->getCurrentStep()}\n";
    echo "  - Status: {$state->getStatus()->value}\n";

    if (extension_loaded('fiber')) {
        echo "\nNote: For true parallel execution, use ActionRunner::runParallel() directly\n";
        echo "with multiple actions. The workflow processes places sequentially.\n";
    }
} catch (Exception $e) {
    echo "✗ Workflow failed: {$e->getMessage()}\n";
}

echo "\n=== Example completed ===\n";
