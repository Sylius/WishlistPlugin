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

namespace Sylius\WishlistPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractPostgreSQLMigration;

final class Version20250427090023 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'This migration renames the wishlist tables from BitBag to Sylius (PostgreSQL).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist RENAME TO sylius_wishlist');
        $this->addSql('ALTER TABLE bitbag_wishlist_product RENAME TO sylius_wishlist_product');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_wishlist RENAME TO bitbag_wishlist');
        $this->addSql('ALTER TABLE sylius_wishlist_product RENAME TO bitbag_wishlist_product');
    }
}
