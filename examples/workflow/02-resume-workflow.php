<?php

declare(strict_types=1);

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
use Symfony\AI\Agent\Workflow\Store\FilesystemWorkflowStore;
use Symfony\AI\Agent\Workflow\WorkflowBuilder;
use Symfony\AI\Agent\Workflow\WorkflowExecutor;
use Symfony\AI\Agent\Workflow\WorkflowState;
use Symfony\AI\Platform\Result\TextResult;

require_once dirname(__DIR__).'/bootstrap.php';

echo "=== Resume Workflow Example ===\n\n";

$workflowConfig = WorkflowBuilder::create('data-processing')
    ->addPlace('start')
    ->addPlace('fetching')
    ->addPlace('validating')
    ->addPlace('processing')
    ->addPlace('completed')
    ->setInitialPlace('start')
    ->addTransition('fetch', 'start', 'fetching')
    ->addTransition('validate', 'fetching', 'validating')
    ->addTransition('process', 'validating', 'processing')
    ->addTransition('complete', 'processing', 'completed')

    // Actions
    ->addActionForPlace('fetching', new Action(
        'fetch-data',
        static fn ($agent, $state) => new TextResult('Data fetched: 1000 records'),
        retryCount: 3
    ))
    ->addActionForPlace('validating', new Action(
        'validate-data',
        static function ($agent, $state) {
            // Simulate failure on first attempt
            if (!isset($state->getContext()['retry_count'])) {
                $state->mergeContext(['retry_count' => 1]);
                throw new RuntimeException('Validation failed - data incomplete');
            }

            return new TextResult('Data validated successfully');
        },
        retryCount: 3
    ))
    ->addActionForPlace('processing', new Action(
        'process-data',
        static fn ($agent, $state) => new TextResult('Data processed: all 1000 records'),
        retryCount: 3
    ))

    ->build();

$store = new FilesystemWorkflowStore(sys_get_temp_dir().'/workflow_states');
$store->setup();

$state = new WorkflowState(
    'data-job-456',
    context: [
        '__marking' => ['start' => 1],
        'dataset' => 'customers-2024',
    ]
);

$mockAgent = new MockAgent();

$executor = new WorkflowExecutor($workflowConfig, $store);

echo "First execution attempt:\n";
echo "  - Workflow ID: {$state->getId()}\n";
echo "  - Initial place: {$state->getCurrentStep()}\n\n";

try {
    $executor->execute($mockAgent, $state);
    echo "✓ Workflow completed on first try\n";
} catch (Exception $e) {
    echo "✗ Workflow failed: {$e->getMessage()}\n";
    echo "  - Current place: {$state->getCurrentStep()}\n";
    echo "  - Status: {$state->getStatus()->value}\n";
    echo '  - Errors: '.count($state->getErrors())."\n\n";
}

echo "\nResuming workflow...\n";
echo "  - Loading from store\n";
echo "  - Previous place: {$state->getCurrentStep()}\n\n";

try {
    $result = $executor->resume('data-job-456', $mockAgent);

    echo "✓ Workflow resumed and completed!\n";
    echo "  - Final place: {$state->getCurrentStep()}\n";
    echo "  - Result: {$result->getContent()}\n";
    echo "  - Status: {$state->getStatus()->value}\n";
} catch (Exception $e) {
    echo "✗ Resume failed: {$e->getMessage()}\n";
}

// Cleanup
$store->remove('data-job-456');
$store->drop();

echo "\n=== Example completed ===\n";
