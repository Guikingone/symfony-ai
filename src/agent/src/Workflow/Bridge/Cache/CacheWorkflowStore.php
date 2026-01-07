<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Bridge\Cache;

use Symfony\AI\Agent\Workflow\ManagedWorkflowStoreInterface;
use Symfony\AI\Agent\Workflow\WorkflowStateInterface;
use Symfony\AI\Agent\Workflow\StoreInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class CacheWorkflowStore implements StoreInterface, ManagedWorkflowStoreInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function setup(array $options = []): void
    {
        // TODO: Implement setup() method.
    }

    public function drop(): void
    {
        // TODO: Implement drop() method.
    }

    public function save(WorkflowStateInterface $state): void
    {
        // TODO: Implement save() method.
    }

    public function load(string $name): WorkflowStateInterface
    {
        // TODO: Implement load() method.
    }
}
