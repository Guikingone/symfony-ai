<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\AI\Store\Command\SetupStoreCommand;

#[CoversClass(SetupStoreCommand::class)]
final class SetupStoreCommandTest extends TestCase
{
    public function testCommandIsConfigured()
    {
        $command = new SetupStoreCommand($this->createMock(ContainerInterface::class));

        $this->assertSame('ai:setup:store', $command->getName());
        $this->assertSame('Prepare the required infrastructure for the store', $command->getDescription());
    }
}
