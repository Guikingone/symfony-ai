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
use Symfony\AI\Agent\Workflow\Bridge\Local\InMemoryStore;
use Symfony\AI\Agent\Workflow\Workflow;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Agent\Workflow\WorkflowStepInterface;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\Template;

require_once dirname(__DIR__).'/bootstrap.php';

$workflow = new Workflow('Tourist guide', new InMemoryStore());

$workflow->addStep(new class implements WorkflowStepInterface {
    public function getName(): string
    {
        return 'start tour';
    }

    public function execute(WorkflowStateInterface $state): void
    {
        $platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());

        $agent = new Agent($platform, 'gpt-4o-mini');

        $result = $agent->call(new MessageBag(
            Message::forSystem(Template::fromString('You are a tourist guide in {country}.')),
            Message::ofUser(Template::fromString('What is the name of the capital in {country}?')),
        ), [
            'template_vars' => [
                'country' => 'France',
            ],
        ]);

        $state->setResult($result);
    }
});

$workflow->addStep(new class implements WorkflowStepInterface {
    public function getName(): string
    {
        return 'continue tour';
    }

    public function execute(WorkflowStateInterface $state): void
    {
        $platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());

        $agent = new Agent($platform, 'gpt-4o-mini');

        if (null === $state->getResult()) {
            return;
        }

        $result = $agent->call(new MessageBag(
            Message::ofUser(Template::fromString('Given the current context: {context}, which activities can we do in this capital?')),
        ), [
            'template_vars' => [
                'history' => $state->getResult()->asText(),
            ],
        ]);

        $state->setResult($result);
    }
});

$workflow->call();
