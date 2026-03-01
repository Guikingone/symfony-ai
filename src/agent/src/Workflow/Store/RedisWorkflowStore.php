<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Store;

use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Agent\Workflow\WorkflowStateNormalizer;
use Symfony\AI\Agent\Workflow\WorkflowStoreInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class RedisWorkflowStore implements WorkflowStoreInterface
{
    public function __construct(
        private readonly \Redis $redis,
        private readonly int $ttl = 3600,
        private readonly string $prefix = 'workflow:',
        private readonly int $lockTimeout = 10,
        private readonly SerializerInterface&NormalizerInterface&DenormalizerInterface $serializer = new Serializer([
            new ArrayDenormalizer(),
            new WorkflowStateNormalizer(),
        ], [new JsonEncoder()]),
    ) {
    }

    public function save(WorkflowStateInterface $state): void
    {
        $key = $this->getKey($state->getId());
        $lockKey = $key.':lock';

        $lockAcquired = $this->redis->set($lockKey, '1', ['NX', 'EX' => $this->lockTimeout]);

        if (!$lockAcquired) {
            throw new \RuntimeException('Could not acquire lock for workflow '.$state->getId());
        }

        try {
            $this->redis->setex($key, $this->ttl, $this->serializer->serialize($state, 'json'));
        } finally {
            $this->redis->del($lockKey);
        }
    }

    public function load(string $id): ?WorkflowStateInterface
    {
        $key = $this->getKey($id);
        $data = $this->redis->get($key);

        if (false === $data) {
            return null;
        }

        return $this->serializer->deserialize($data, WorkflowStateInterface::class, 'json');
    }

    public function remove(string $id): void
    {
        $key = $this->getKey($id);

        $this->redis->del($key);
    }

    private function getKey(string $id): string
    {
        return $this->prefix.$id;
    }
}
