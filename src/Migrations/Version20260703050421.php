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

final class Version20260703050421 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Renames wishlist tables/indexes from BitBag to Sylius and adds quantity column.';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(!$schema->hasTable('bitbag_wishlist'), 'Table "bitbag_wishlist" does not exist, nothing to rename.');

        $this->addSql('ALTER TABLE bitbag_wishlist RENAME TO sylius_wishlist');
        $this->addSql('ALTER TABLE bitbag_wishlist_product RENAME TO sylius_wishlist_product');
        $this->addSql('ALTER SEQUENCE bitbag_wishlist_id_seq RENAME TO sylius_wishlist_id_seq');
        $this->addSql('ALTER SEQUENCE bitbag_wishlist_product_id_seq RENAME TO sylius_wishlist_product_id_seq');
        $this->addSql('ALTER INDEX idx_578d4e77a45d93bf RENAME TO idx_635a71dea45d93bf');
        $this->addSql('ALTER INDEX idx_578d4e7772f5a1aa RENAME TO idx_635a71de72f5a1aa');
        $this->addSql('ALTER INDEX idx_3dbe67a0fb8e54cd RENAME TO idx_8d0d7c6dfb8e54cd');
        $this->addSql('ALTER INDEX idx_3dbe67a04584665a RENAME TO idx_8d0d7c6d4584665a');
        $this->addSql('ALTER INDEX idx_3dbe67a03b69a9af RENAME TO idx_8d0d7c6d3b69a9af');
        $this->addSql('ALTER TABLE sylius_wishlist_product ADD quantity INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(!$schema->hasTable('sylius_wishlist'), 'Table "sylius_wishlist" does not exist, nothing to revert.');

        $this->addSql('ALTER TABLE sylius_wishlist_product DROP quantity');
        $this->addSql('ALTER INDEX idx_635a71dea45d93bf RENAME TO idx_578d4e77a45d93bf');
        $this->addSql('ALTER INDEX idx_635a71de72f5a1aa RENAME TO idx_578d4e7772f5a1aa');
        $this->addSql('ALTER INDEX idx_8d0d7c6d4584665a RENAME TO idx_3dbe67a04584665a');
        $this->addSql('ALTER INDEX idx_8d0d7c6d3b69a9af RENAME TO idx_3dbe67a03b69a9af');
        $this->addSql('ALTER INDEX idx_8d0d7c6dfb8e54cd RENAME TO idx_3dbe67a0fb8e54cd');
        $this->addSql('ALTER SEQUENCE sylius_wishlist_id_seq RENAME TO bitbag_wishlist_id_seq');
        $this->addSql('ALTER SEQUENCE sylius_wishlist_product_id_seq RENAME TO bitbag_wishlist_product_id_seq');
        $this->addSql('ALTER TABLE sylius_wishlist RENAME TO bitbag_wishlist');
        $this->addSql('ALTER TABLE sylius_wishlist_product RENAME TO bitbag_wishlist_product');
    }
}
