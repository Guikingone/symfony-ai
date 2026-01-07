<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\Workflow\Bridge\Local\InMemoryWorkflowStore;
use Symfony\AI\Agent\Workflow\Workflow;
use Symfony\AI\Agent\Workflow\WorkflowStep;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());

$agent = new Agent($platform, 'gpt-4o-mini');

$workflow = new Workflow('', new InMemoryWorkflowStore());
$workflow->addStep(new WorkflowStep());

$workflow->call();
