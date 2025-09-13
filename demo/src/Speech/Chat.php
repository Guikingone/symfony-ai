<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Speech;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Content\Audio;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Speech\SpeechResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'audio-chat';

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'ai.agent.speech')]
        private readonly AgentInterface $agent,
    ) {
    }

    public function say(string $base64audio): void
    {
        // Convert base64 to temporary binary file
        $path = tempnam(sys_get_temp_dir(), 'audio-').'.wav';
        file_put_contents($path, base64_decode($base64audio));

        $messages = $this->loadMessages();
        $messages->add(Message::ofUser(Audio::fromFile($path)));

        /** @var SpeechResult $result */
        $result = $this->agent->call($messages);

        $assistantMessage = Message::ofAssistant($result->getContent());
        $messages->add($assistantMessage);

        $assistantMessage->getMetadata()->add('speech', $result->asDataUri('audio/mpeg'));

        $this->saveMessages($messages);
    }

    public function loadMessages(): MessageBag
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, new MessageBag());
    }

    public function submitMessage(string $message): void
    {
        $messages = $this->loadMessages();

        $messages->add(Message::ofUser($message));

        /** @var SpeechResult $result */
        $result = $this->agent->call($messages);

        $assistantMessage = Message::ofAssistant($result->getContent());
        $messages->add($assistantMessage);

        $assistantMessage->getMetadata()->add('speech', $result->asDataUri('audio/mpeg'));

        $this->saveMessages($messages);
    }

    public function reset(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    private function saveMessages(MessageBag $messages): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $messages);
    }
}
