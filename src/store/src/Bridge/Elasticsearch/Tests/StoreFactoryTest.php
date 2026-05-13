<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Bridge\Elasticsearch\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Store\Bridge\Elasticsearch\Store;
use Symfony\AI\Store\Bridge\Elasticsearch\StoreFactory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;

final class StoreFactoryTest extends TestCase
{
    public function testStoreCanBeCreatedWithHttpClientAndRequiredInfos()
    {
        $store = StoreFactory::create('my-index', 'http://127.0.0.1:9200');

        $this->assertInstanceOf(Store::class, $store);
    }

    public function testStoreCanBeCreatedWithScopingHttpClient()
    {
        $store = StoreFactory::create('my-index', httpClient: ScopingHttpClient::forBaseUri(HttpClient::create(), 'http://127.0.0.1:9200/'));

        $this->assertInstanceOf(Store::class, $store);
    }
}
