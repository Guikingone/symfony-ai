<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Bridge\HelixDb\Tests;

use PHPUnit\Framework\Attributes\Group;
use Symfony\AI\Store\Bridge\HelixDb\Store;
use Symfony\AI\Store\StoreInterface;
use Symfony\AI\Store\Test\AbstractStoreIntegrationTestCase;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Requires a running HelixDB instance with the bridge's "Resources/*.hx" queries deployed.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
#[Group('integration')]
final class IntegrationTest extends AbstractStoreIntegrationTestCase
{
    protected static function createStore(): StoreInterface
    {
        return new Store(
            HttpClient::create(),
            'http://127.0.0.1:6969',
            embeddingsDimension: 3,
        );
    }
}
