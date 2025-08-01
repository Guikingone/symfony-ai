<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Platform\Bridge\HuggingFace\PlatformFactory;
use Symfony\AI\Platform\Bridge\HuggingFace\Task;
use Symfony\AI\Platform\Model;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = PlatformFactory::create(env('HUGGINGFACE_KEY'), httpClient: http_client());
$model = new Model('facebook/bart-large-cnn');

$longText = <<<TEXT
    The tower is 324 metres (1,063 ft) tall, about the same height as an 81-storey building, and the tallest structure
    in Paris. Its base is square, measuring 125 metres (410 ft) on each side. During its construction, the Eiffel Tower
    surpassed the Washington Monument to become the tallest man-made structure in the world, a title it held for 41
    years until the Chrysler Building in New York City was finished in 1930. It was the first structure to reach a
    height of 300 metres. Due to the addition of a broadcasting aerial at the top of the tower in 1957, it is now taller
    than the Chrysler Building by 5.2 metres (17 ft). Excluding transmitters, the Eiffel Tower is the second tallest
    free-standing structure in France after the Millau Viaduct.
    TEXT;

$result = $platform->invoke($model, $longText, [
    'task' => Task::SUMMARIZATION,
]);

echo $result->asText().\PHP_EOL;
