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
interface WorkflowStateInterface
{
    public function setResult(ResultInterface $result): void;

    public function getResult(): ?ResultInterface;

    public function addContext(string $key, mixed $value = null): void;

    public function getContext(): array;
}
