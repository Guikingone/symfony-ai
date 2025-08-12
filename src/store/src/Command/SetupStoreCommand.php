<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Store\Command;

use Psr\Container\ContainerInterface;
use Symfony\AI\Store\SetupableStoreInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Guillaume Loulier <personal@guillaumeloulier.fr>
 */
#[AsCommand(name: 'ai:setup:store', description: 'Prepare the required infrastructure for the store')]
final class SetupStoreCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $storeLocator,
        private readonly array $storeNames = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('store', InputArgument::OPTIONAL, 'Name of the store to setup', null)
            ->setHelp(<<<EOF
The <info>%command.name%</info> command setups the store:

    <info>php %command.full_name%</info>

Or a specific store only:

    <info>php %command.full_name% <transport></info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $storeNames = $this->storeNames;

        if ($store = $input->getArgument('store')) {
            if (!$this->storeLocator->has($store)) {
                throw new \RuntimeException(\sprintf('The "%s" store does not exist.', $store));
            }
            $storeNames = [$store];
        }

        foreach ($storeNames as $storeName) {
            $store = $this->storeLocator->get($storeName);
            if (!$store instanceof SetupableStoreInterface) {
                $io->note(\sprintf('The "%s" store does not support setup.', $storeName));
                continue;
            }

            try {
                $store->setup();
                $io->success(\sprintf('The "%s" store was set up successfully.', $storeName));
            } catch (\Exception $e) {
                throw new \RuntimeException(\sprintf('An error occurred while setting up the "%s" store: ', $storeName).$e->getMessage(), 0, $e);
            }
        }

        return Command::SUCCESS;
    }
}
