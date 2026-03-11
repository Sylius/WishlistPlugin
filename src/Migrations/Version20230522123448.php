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

final class Version20230522123448 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Add missing fields and indexes to wishlists (PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('bitbag_wishlist')->hasColumn('channel_id')) {
            return;
        }

        $this->addSql('ALTER TABLE bitbag_wishlist ADD channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bitbag_wishlist ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE bitbag_wishlist ADD CONSTRAINT FK_578D4E7772F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_578D4E7772F5A1AA ON bitbag_wishlist (channel_id)');

        $this->addSql('DROP INDEX UNIQ_578D4E77A45D93BF');
        $this->addSql('CREATE INDEX IDX_578D4E77A45D93BF ON bitbag_wishlist (shop_user_id)');
        $this->addSql('DROP INDEX UNIQ_578D4E775F37A13B');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist DROP CONSTRAINT FK_578D4E7772F5A1AA');
        $this->addSql('DROP INDEX IDX_578D4E7772F5A1AA');
        $this->addSql('ALTER TABLE bitbag_wishlist DROP channel_id');
        $this->addSql('ALTER TABLE bitbag_wishlist DROP name');

        $this->addSql('DROP INDEX IDX_578D4E77A45D93BF');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_578D4E77A45D93BF ON bitbag_wishlist (shop_user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_578D4E775F37A13B ON bitbag_wishlist (token)');
    }
}
