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
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Tool\YouTubeTranscriber;
use Symfony\AI\Agent\Toolbox\Toolbox;
use Symfony\AI\Platform\Bridge\OpenAi\Gpt;
use Symfony\AI\Platform\Bridge\OpenAi\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = PlatformFactory::create(env('OPENAI_API_KEY'), http_client());
$model = new Gpt(Gpt::GPT_4O_MINI);

$transcriber = new YouTubeTranscriber(http_client());
$toolbox = new Toolbox([$transcriber], logger: logger());
$processor = new AgentProcessor($toolbox);
$agent = new Agent($platform, $model, [$processor], [$processor], logger());

$messages = new MessageBag(Message::ofUser('Please summarize this video for me: https://www.youtube.com/watch?v=6uXW-ulpj0s'));
$result = $agent->call($messages);

echo $result->getContent().\PHP_EOL;
