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
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;

require_once dirname(__DIR__).'/bootstrap.php';

echo "=== Guards and Conditionals Example ===\n\n";

$workflowConfig = WorkflowBuilder::create('content-moderation')
    ->addPlace('submitted')
    ->addPlace('auto_approved')
    ->addPlace('manual_review')
    ->addPlace('approved')
    ->addPlace('rejected')
    ->setInitialPlace('submitted')

    // Automatic approval path
    ->addTransition('auto_approve', 'submitted', 'auto_approved')
    ->addGuardForTransition('auto_approve', static function (WorkflowStateInterface $state): bool {
        $context = $state->getContext();
        $score = $context['confidence_score'] ?? 0;

        return $score >= 0.9; // High confidence = auto approve
    })

    // Manual review path
    ->addTransition('needs_review', 'submitted', 'manual_review')
    ->addGuardForTransition('needs_review', static function (WorkflowStateInterface $state): bool {
        $context = $state->getContext();
        $score = $context['confidence_score'] ?? 0;

        return $score < 0.9 && $score >= 0.3; // Medium confidence = review
    })

    // Direct rejection path
    ->addTransition('auto_reject', 'submitted', 'rejected')
    ->addGuardForTransition('auto_reject', static function (WorkflowStateInterface $state): bool {
        $context = $state->getContext();
        $score = $context['confidence_score'] ?? 0;

        return $score < 0.3; // Low confidence = reject
    })

    // From manual review
    ->addTransition('approve_after_review', 'manual_review', 'approved')
    ->addTransition('reject_after_review', 'manual_review', 'rejected')

    ->addActionForPlace('auto_approved', new Action(
        'notify-auto-approval',
        static fn ($agent, $state): ResultInterface => new TextResult('User notified of automatic approval')
    ))
    ->addActionForPlace('manual_review', new Action(
        'assign-reviewer',
        static fn ($agent, $state): ResultInterface => new TextResult('Content assigned to human reviewer')
    ))
    ->build();

// Test case 1: High confidence - auto approve
echo "Test 1: High confidence content (score: 0.95)\n";
$state1 = new WorkflowState(
    'content-001',
    context: [
        '__marking' => ['submitted' => 1],
        'confidence_score' => 0.95,
        'content_type' => 'article',
    ]
);

$executor = new WorkflowExecutor($workflowConfig, new InMemoryWorkflowStore());

try {
    $executor->execute(new MockAgent(), $state1);
    echo "  ✓ Result: {$state1->getCurrentStep()}\n";
    echo "  ✓ {$state1->getContext()['last_result']}\n\n";
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n\n";
}

// Test case 2: Medium confidence - manual review
echo "Test 2: Medium confidence content (score: 0.65)\n";
$state2 = new WorkflowState(
    'content-002',
    context: [
        '__marking' => ['submitted' => 1],
        'confidence_score' => 0.65,
        'content_type' => 'comment',
    ]
);

try {
    $executor->execute(new MockAgent(), $state2);
    echo "  ✓ Result: {$state2->getCurrentStep()}\n";
    echo "  ✓ {$state2->getContext()['last_result']}\n\n";
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n\n";
}

// Test case 3: Low confidence - auto reject
echo "Test 3: Low confidence content (score: 0.15)\n";
$state3 = new WorkflowState(
    'content-003',
    context: [
        '__marking' => ['submitted' => 1],
        'confidence_score' => 0.15,
        'content_type' => 'spam',
    ]
);

try {
    $executor->execute(new MockAgent(), $state3);
    echo "  ✓ Result: {$state3->getCurrentStep()}\n";
    if ('rejected' === $state3->getCurrentStep()) {
        echo "  ✓ Content automatically rejected\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: {$e->getMessage()}\n";
}

echo "\n=== Example completed ===\n";
echo "\nThis demonstrates how guards control workflow transitions based on state context.\n";
echo "Each content item follows a different path based on its confidence score.\n";
