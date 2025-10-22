<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Chat;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Result\TextResult;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
final class Chat implements ChatInterface
{
    public function __construct(
        private readonly AgentInterface $agent,
        private readonly MessageStoreInterface&ManagedStoreInterface $store,
        private string $name = '_chat',
    ) {
    }

    public function initiate(MessageBag $messages): void
    {
        $messages->setChat($this->name);

        $this->store->drop();
        $this->store->save($messages, $this->name);
    }

    public function submit(UserMessage $message): AssistantMessage
    {
        $messages = $this->store->load($this->name);

        $messages->add($message);
        $result = $this->agent->call($messages);

        \assert($result instanceof TextResult);

        $assistantMessage = Message::ofAssistant($result->getContent());
        $messages->add($assistantMessage);

        $this->store->save($messages, $this->name);

        return $assistantMessage;
    }

    public function fork(UserMessage $message, string $identifier): self
    {
        $clone = clone $this;

        $currentMessages = $this->store->load($this->name);

        $forkedMessages = $currentMessages->fork($message);
        $forkedMessages->setChat($identifier);

        $result = $this->agent->call($forkedMessages);

        \assert($result instanceof TextResult);

        $assistantMessage = Message::ofAssistant($result->getContent());
        $forkedMessages->add($assistantMessage);

        $this->store->save($forkedMessages, $identifier);

        $clone->name = $identifier;

        return $clone;
    }
}
