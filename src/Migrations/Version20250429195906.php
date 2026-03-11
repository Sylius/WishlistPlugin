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

final class Version20250429195906 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'This migration renames indexes in sylius_wishlist and sylius_wishlist_product tables (PostgreSQL).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_578d4e77a45d93bf RENAME TO IDX_635A71DEA45D93BF');
        $this->addSql('ALTER INDEX idx_578d4e7772f5a1aa RENAME TO IDX_635A71DE72F5A1AA');
        $this->addSql('ALTER INDEX idx_3dbe67a0fb8e54cd RENAME TO IDX_8D0D7C6DFB8E54CD');
        $this->addSql('ALTER INDEX idx_3dbe67a04584665a RENAME TO IDX_8D0D7C6D4584665A');
        $this->addSql('ALTER INDEX idx_3dbe67a03b69a9af RENAME TO IDX_8D0D7C6D3B69A9AF');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX idx_635a71dea45d93bf RENAME TO IDX_578D4E77A45D93BF');
        $this->addSql('ALTER INDEX idx_635a71de72f5a1aa RENAME TO IDX_578D4E7772F5A1AA');
        $this->addSql('ALTER INDEX idx_8d0d7c6d4584665a RENAME TO IDX_3DBE67A04584665A');
        $this->addSql('ALTER INDEX idx_8d0d7c6d3b69a9af RENAME TO IDX_3DBE67A03B69A9AF');
        $this->addSql('ALTER INDEX idx_8d0d7c6dfb8e54cd RENAME TO IDX_3DBE67A0FB8E54CD');
    }
}
