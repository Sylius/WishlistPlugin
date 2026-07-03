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

final class Version20260702170135 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Creates sylius_wishlist and sylius_wishlist_product tables (fresh install, PostgreSQL).';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('bitbag_wishlist') || $schema->hasTable('sylius_wishlist')) {
            $this->markAsExecuted($this->getVersion());
            $this->skipIf(true, 'Table "bitbag_wishlist" or "sylius_wishlist" already exists, skipping fresh install migration.');
        }

        $this->addSql('CREATE SEQUENCE sylius_wishlist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sylius_wishlist_product_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE sylius_wishlist (id INT NOT NULL, shop_user_id INT DEFAULT NULL, channel_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, token VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_635A71DEA45D93BF ON sylius_wishlist (shop_user_id)');
        $this->addSql('CREATE INDEX IDX_635A71DE72F5A1AA ON sylius_wishlist (channel_id)');
        $this->addSql('CREATE INDEX token_idx ON sylius_wishlist (token)');
        $this->addSql('CREATE INDEX channel_shop_user_token_idx ON sylius_wishlist (channel_id, shop_user_id, token)');
        $this->addSql('CREATE TABLE sylius_wishlist_product (id INT NOT NULL, wishlist_id INT NOT NULL, product_id INT DEFAULT NULL, variant_id INT DEFAULT NULL, quantity INT DEFAULT 1 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D0D7C6DFB8E54CD ON sylius_wishlist_product (wishlist_id)');
        $this->addSql('CREATE INDEX IDX_8D0D7C6D4584665A ON sylius_wishlist_product (product_id)');
        $this->addSql('CREATE INDEX IDX_8D0D7C6D3B69A9AF ON sylius_wishlist_product (variant_id)');
        $this->addSql('ALTER TABLE sylius_wishlist ADD CONSTRAINT FK_635A71DEA45D93BF FOREIGN KEY (shop_user_id) REFERENCES sylius_shop_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_wishlist ADD CONSTRAINT FK_635A71DE72F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_wishlist_product ADD CONSTRAINT FK_8D0D7C6DFB8E54CD FOREIGN KEY (wishlist_id) REFERENCES sylius_wishlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_wishlist_product ADD CONSTRAINT FK_8D0D7C6D4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_wishlist_product ADD CONSTRAINT FK_8D0D7C6D3B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('sylius_wishlist')) {
            $this->markAsExecuted($this->getVersion());
            $this->skipIf(true, 'Table "sylius_wishlist" does not exist, nothing to revert.');
        }
        
        $this->addSql('DROP SEQUENCE sylius_wishlist_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE sylius_wishlist_product_id_seq CASCADE');
        $this->addSql('ALTER TABLE sylius_wishlist DROP CONSTRAINT FK_635A71DEA45D93BF');
        $this->addSql('ALTER TABLE sylius_wishlist DROP CONSTRAINT FK_635A71DE72F5A1AA');
        $this->addSql('ALTER TABLE sylius_wishlist_product DROP CONSTRAINT FK_8D0D7C6DFB8E54CD');
        $this->addSql('ALTER TABLE sylius_wishlist_product DROP CONSTRAINT FK_8D0D7C6D4584665A');
        $this->addSql('ALTER TABLE sylius_wishlist_product DROP CONSTRAINT FK_8D0D7C6D3B69A9AF');
        $this->addSql('DROP TABLE sylius_wishlist');
        $this->addSql('DROP TABLE sylius_wishlist_product');
    }
}
