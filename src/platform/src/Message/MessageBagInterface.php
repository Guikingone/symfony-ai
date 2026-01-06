<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Message;

use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\TimeBasedUidInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
interface MessageBagInterface extends \Countable, \IteratorAggregate
{
    public function getId(): AbstractUid&TimeBasedUidInterface;

    public function add(MessageInterface $message): void;

    public function getMessages(): array;

    public function getSystemMessage(): ?SystemMessage;

    public function getUserMessage(): ?UserMessage;

    public function with(MessageInterface $message): self;

    public function merge(self $messageBag): self;

    public function withoutSystemMessage(): self;

    public function withSystemMessage(SystemMessage $message): self;

    public function containsAudio(): bool;

    public function containsImage(): bool;
}
