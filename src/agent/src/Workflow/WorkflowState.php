<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow;

use Symfony\AI\Platform\Result\ResultInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class WorkflowState implements WorkflowStateInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    private ?ResultInterface $result = null;

    public function __construct(
        private readonly array $options = [],
    ) {
    }

    public function setResult(ResultInterface $result): void
    {
        $this->result = $result;
    }

    public function getResult(): ?ResultInterface
    {
        return $this->result;
    }

    public function addContext(string $key, mixed $value = null): void
    {
        $this->context[$key] = $value;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
