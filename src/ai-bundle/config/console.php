<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\AI\AiBundle\Command\SetupStoreCommand;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('console.command.ai_setup_stores', SetupStoreCommand::class)
        ->args([
            service('ai.store.receiver_locator'),
            [], // Receiver names
        ])
        ->tag('console.command');
};
