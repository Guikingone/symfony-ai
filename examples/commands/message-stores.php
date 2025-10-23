<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(__DIR__).'/bootstrap.php';

use Symfony\AI\Chat\Bridge\HttpFoundation\SessionStore;
use Symfony\AI\Chat\Bridge\Local\CacheStore;
use Symfony\AI\Chat\Bridge\Local\InMemoryStore;
use Symfony\AI\Chat\Bridge\Meilisearch\MessageStore as MeilisearchMessageStore;
use Symfony\AI\Chat\Bridge\Pogocache\MessageStore as PogocacheMessageStore;
use Symfony\AI\Chat\Command\DropStoreCommand;
use Symfony\AI\Chat\Command\SetupStoreCommand;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\MonotonicClock;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

$factories = [
    'cache' => static fn (): CacheStore => new CacheStore(new ArrayAdapter(), cacheKey: 'symfony'),
    'meilisearch' => static fn (): MeilisearchMessageStore => new MeilisearchMessageStore(
        http_client(),
        env('MEILISEARCH_HOST'),
        env('MEILISEARCH_API_KEY'),
        new MonotonicClock(),
        'symfony',
    ),
    'memory' => static fn (): InMemoryStore => new InMemoryStore('symfony'),
    'pogocache' => static fn (): PogocacheMessageStore => new PogocacheMessageStore(
        http_client(),
        env('POGOCACHE_HOST'),
        env('POGOCACHE_PASSWORD'),
        'symfony',
    ),
    'session' => static function (): SessionStore {
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new SessionStore($requestStack, 'symfony');
    },
];

$storesIds = array_keys($factories);

$application = new Application();
$application->setAutoExit(false);
$application->setCatchExceptions(false);
$application->addCommands([
    new SetupStoreCommand(new ServiceLocator($factories)),
    new DropStoreCommand(new ServiceLocator($factories)),
]);

foreach ($storesIds as $store) {
    $setupOutputCode = $application->run(new ArrayInput([
        'command' => 'ai:message-store:setup',
        'store' => $store,
    ]), new ConsoleOutput());

    $dropOutputCode = $application->run(new ArrayInput([
        'command' => 'ai:message-store:drop',
        'store' => $store,
        '--force' => true,
    ]), new ConsoleOutput());
}
