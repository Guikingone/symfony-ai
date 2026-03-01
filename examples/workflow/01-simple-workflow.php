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
use Symfony\AI\Agent\Workflow\Store\InMemoryWorkflowStore;
use Symfony\AI\Agent\Workflow\WorkflowBuilder;
use Symfony\AI\Agent\Workflow\WorkflowExecutor;
use Symfony\AI\Agent\Workflow\WorkflowState;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;

require_once dirname(__DIR__).'/bootstrap.php';

echo "=== Simple Workflow Example ===\n\n";

// Create a simple 3-step workflow
$workflowConfig = WorkflowBuilder::create('order-workflow')
    ->addPlace('draft')
    ->addPlace('submitted')
    ->addPlace('processed')
    ->setInitialPlace('draft')
    ->addTransition('submit', 'draft', 'submitted')
    ->addTransition('process', 'submitted', 'processed')
    ->addActionForPlace('submitted', new Action(
        'validate-order',
        static fn ($agent, $state): ResultInterface => new TextResult('Order validated'),
        retryCount: 3
    ))
    ->addActionForPlace('processed', new Action(
        'complete-order',
        static fn ($agent, $state): ResultInterface => new TextResult('Order completed'),
        retryCount: 3
    ))
    ->addGuardForTransition('submit', static function ($state): bool {
        $context = $state->getContext();

        return !empty($context['order_id']);
    })
    ->build();

// Create workflow state with initial place marking
$state = new WorkflowState(
    'order-123',
    context: [
        '__marking' => ['draft' => 1],
        'order_id' => 'ORD-001',
        'customer' => 'John Doe',
    ]
);

echo "Initial state:\n";
echo "  - Current place: {$state->getCurrentStep()}\n";
echo "  - Order ID: {$state->getContext()['order_id']}\n\n";

$executor = new WorkflowExecutor(
    $workflowConfig,
    new InMemoryWorkflowStore(),
);

echo "Executing workflow...\n\n";
try {
    $result = $executor->execute(new MockAgent(), $state);

    echo "Workflow completed successfully!\n";
    echo "  - Final place: {$state->getCurrentStep()}\n";
    echo "  - Result: {$result->getContent()}\n";
    echo "  - Status: {$state->getStatus()->value}\n";

    if (isset($state->getContext()['last_action'])) {
        echo "  - Last action: {$state->getContext()['last_action']}\n";
    }
} catch (Exception $e) {
    echo "Workflow failed: {$e->getMessage()}\n";
    echo "  - Current place: {$state->getCurrentStep()}\n";
    echo '  - Errors: '.count($state->getErrors())."\n";
}

echo "\n=== Example completed ===\n";
