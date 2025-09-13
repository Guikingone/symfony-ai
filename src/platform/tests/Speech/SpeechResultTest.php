<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Tests\Speech;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\PlainConverter;
use Symfony\AI\Platform\Result\BinaryResult;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Speech\SpeechResult;

final class SpeechResultTest extends TestCase
{
    public function testGetContentReturnsTextContent()
    {
        $textResult = new TextResult('Hello world');
        $speechResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data')), new InMemoryRawResult());

        $result = new SpeechResult($textResult, $speechResult);

        $this->assertSame('Hello world', $result->getContent());
    }

    public function testGetTextResultReturnsOriginalResult()
    {
        $textResult = new TextResult('Hello world');
        $speechResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data')), new InMemoryRawResult());

        $result = new SpeechResult($textResult, $speechResult);

        $this->assertSame($textResult, $result->getTextResult());
    }

    public function testAsBinaryReturnsSpeechBinary()
    {
        $textResult = new TextResult('Hello world');
        $speechResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data')), new InMemoryRawResult());

        $result = new SpeechResult($textResult, $speechResult);

        $this->assertSame('audio-data', $result->asBinary());
    }

    public function testAsDataUriReturnsSpeechDataUri()
    {
        $textResult = new TextResult('Hello world');
        $speechResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data', 'audio/mpeg')), new InMemoryRawResult());

        $result = new SpeechResult($textResult, $speechResult);

        $this->assertSame('data:audio/mpeg;base64,'.base64_encode('audio-data'), $result->asDataUri());
    }

    public function testAsDataUriWithCustomMimeType()
    {
        $textResult = new TextResult('Hello world');
        $speechResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data', 'audio/mpeg')), new InMemoryRawResult());

        $result = new SpeechResult($textResult, $speechResult);

        $this->assertSame('data:audio/wav;base64,'.base64_encode('audio-data'), $result->asDataUri('audio/wav'));
    }

    public function testGetMetadataMergesAllSubResults()
    {
        $textResult = new TextResult('Hello world');
        $textResult->getMetadata()->add('token_usage', 42);

        $speechDeferredResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data')), new InMemoryRawResult());
        $speechDeferredResult->getMetadata()->add('audio_duration', 3.5);

        $result = new SpeechResult($textResult, $speechDeferredResult);
        $result->getMetadata()->add('own_key', 'own_value');

        $metadata = $result->getMetadata();

        $this->assertSame('own_value', $metadata->get('own_key'));
        $this->assertSame(42, $metadata->get('token_usage'));
        $this->assertSame(3.5, $metadata->get('audio_duration'));
    }

    public function testAsFileWritesSpeechToFile()
    {
        $textResult = new TextResult('Hello world');
        $speechResult = new DeferredResult(new PlainConverter(new BinaryResult('audio-data')), new InMemoryRawResult());

        $result = new SpeechResult($textResult, $speechResult);

        $tmpFile = sys_get_temp_dir().'/speech_result_test_'.uniqid().'.mp3';

        try {
            $result->asFile($tmpFile);
            $this->assertSame('audio-data', file_get_contents($tmpFile));
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
