<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\Ollama\Ollama;
use Symfony\AI\Platform\Bridge\Ollama\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\ModelCatalog\DynamicModelCatalog;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = PlatformFactory::create(env('OLLAMA_HOST_URL'), http_client(), new DynamicModelCatalog(Ollama::class));

$messages = new MessageBag(
    Message::forSystem('You are a helpful assistant.'),
    Message::ofUser('Tina has one brother and one sister. How many sisters do Tina\'s siblings have?'),
);

try {
    $result = $platform->invoke(env('OLLAMA_LLM'), $messages);
    echo $result->asText().\PHP_EOL;
} catch (InvalidArgumentException $e) {
    echo $e->getMessage()."\nMaybe use a different model?\n";
}
