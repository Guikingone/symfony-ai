<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Workflow\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\AI\Agent\Workflow\ManagedStoreInterface;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
#[AsCommand('ai:workflow-store:setup', description: 'Drop the required infrastructure for the store')]
final class SetupWorkflowStoreCommand extends Command
{

    /**
     * @param ServiceLocator<ManagedStoreInterface> $stores
     */
    public function __construct(
        private readonly ServiceLocator $stores,
    ) {
        parent::__construct();
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('store')) {
            $suggestions->suggestValues($this->getStoreNames());
        }
    }
}
