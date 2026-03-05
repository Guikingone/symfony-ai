<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Bridge\Redis;

use Symfony\AI\Agent\Exception\WorkflowStateNotFoundException;
use Symfony\AI\Agent\Workflow\AbstractWorkflowStateStore;
use Symfony\AI\Agent\Workflow\ListableWorkflowStateStoreInterface;
use Symfony\AI\Agent\Workflow\ManagedWorkflowStateStoreInterface;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;

/**
 * Workflow state store backed by Redis.
 *
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowStateStore extends AbstractWorkflowStateStore implements ListableWorkflowStateStoreInterface, ManagedWorkflowStateStoreInterface
{
    public function __construct(
        private readonly \Redis $redis,
        private readonly string $keyPrefix = '_workflow_state:',
    ) {
        parent::__construct();
    }

    public function setup(): void
    {
        $this->redis->ping();
    }

    public function drop(): void
    {
        $iterator = null;

        do {
            $keys = $this->redis->scan($iterator, $this->keyPrefix.'*');

            if (false !== $keys && [] !== $keys) {
                $this->redis->del($keys);
            }
        } while ($iterator > 0);
    }

    public function save(WorkflowStateInterface $state): void
    {
        $this->redis->set($this->keyPrefix.$state->getId(), $this->serializer->serialize($state, 'json'));
    }

    public function load(string $id): WorkflowStateInterface
    {
        $data = $this->redis->get($this->keyPrefix.$id);

        if (!\is_string($data)) {
            throw new WorkflowStateNotFoundException(\sprintf('Workflow state with id "%s" not found.', $id));
        }

        return $this->serializer->deserialize($data, WorkflowStateInterface::class, 'json');
    }

    public function has(string $id): bool
    {
        return (bool) $this->redis->exists($this->keyPrefix.$id);
    }

    public function delete(string $id): void
    {
        $this->redis->del($this->keyPrefix.$id);
    }

    public function list(): iterable
    {
        $iterator = null;
        $prefixLength = \strlen($this->keyPrefix);

        do {
            $keys = $this->redis->scan($iterator, $this->keyPrefix.'*');

            if (false === $keys) {
                continue;
            }

            foreach ($keys as $key) {
                yield substr($key, $prefixLength);
            }
        } while ($iterator > 0);
    }
}
