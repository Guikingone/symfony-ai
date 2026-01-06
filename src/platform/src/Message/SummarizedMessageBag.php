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

use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\TimeBasedUidInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
final class SummarizedMessageBag implements MessageBagInterface
{
    private MessageBagInterface $messages;

    private ?AssistantMessage $summary = null;

    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly string $model,
        MessageInterface ...$messages,
    ) {
        $this->messages = new MessageBag(...$messages);
    }

    public function getId(): AbstractUid&TimeBasedUidInterface
    {
        return $this->messages->getId();
    }

    public function add(MessageInterface $message): void
    {
        $summarizedMessages = $this->platform->invoke($this->model, new MessageBag(
            Message::forSystem(Template::string('Progressively summarize the lines of conversation provided, adding onto the previous summary returning a new summary then translating the summary back to the language used by the user: {summary}')),
            Message::ofAssistant(Template::string('New entry: content: {content}, role: {role}')),
        ), [
            'template_vars' => [
                'summary' => $this->summary?->getContent() ?? '',
                'content' => $message->getContent(),
                'role' => $message->getRole(),
            ],
        ]);

        $this->summary = new AssistantMessage($summarizedMessages->asText());
    }

    public function getMessages(): array
    {
        if (null === $this->summary) {
            return [];
        }

        return [$this->summary];
    }

    public function getSystemMessage(): ?SystemMessage
    {
        return $this->messages->getSystemMessage();
    }

    public function getUserMessage(): ?UserMessage
    {
        return $this->messages->getUserMessage();
    }

    public function with(MessageInterface $message): MessageBagInterface
    {
        return $this->messages->with($message);
    }

    public function merge(MessageBagInterface $messageBag): MessageBagInterface
    {
        return $this->messages->merge($messageBag);
    }

    public function withoutSystemMessage(): MessageBagInterface
    {
        return $this->messages->withoutSystemMessage();
    }

    public function withSystemMessage(SystemMessage $message): MessageBagInterface
    {
        return $this->messages->withSystemMessage($message);
    }

    public function containsAudio(): bool
    {
        return $this->messages->containsAudio();
    }

    public function containsImage(): bool
    {
        return $this->messages->containsImage();
    }

    public function count(): int
    {
        return $this->messages->count();
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->messages->getIterator();
    }
}
