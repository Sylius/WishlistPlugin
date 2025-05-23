<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\WishlistPlugin\Command;

use Sylius\WishlistPlugin\Repository\WishlistRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'sylius:wishlist:remove-guest-wishlists',
    description: 'Removes guest wishlists',
)]
final class RemoveGuestWishlistsCommand extends Command
{
    public function __construct(private readonly WishlistRepositoryInterface $wishlistRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'date',
            'd',
            InputOption::VALUE_OPTIONAL,
            'The date to remove wishlists updated before (format: d-m-Y)',
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $updatedAt = $input->getOption('date');
        if (null !== $updatedAt) {
            $updatedAt = \DateTime::createFromFormat('d-m-Y', $updatedAt);
            if (!$updatedAt instanceof \DateTimeInterface) {
                $output->writeln('<error>Invalid date format. Please use d-m-Y format.</error>');

                return Command::FAILURE;
            }
            $wishlists = $this->wishlistRepository->findAllAnonymousUpdatedAtEarlierThan($updatedAt);
        } else {
            $wishlists = $this->wishlistRepository->findAllByAnonymous();
        }

        foreach ($wishlists as $wishlist) {
            $this->wishlistRepository->remove($wishlist);
        }

        $output->writeln(sprintf('Removed %d guest wishlists', \count($wishlists)));

        return Command::SUCCESS;
    }
}
