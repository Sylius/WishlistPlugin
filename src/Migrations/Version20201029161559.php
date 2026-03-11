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

final class Version20201029161559 extends AbstractPostgreSQLMigration
{
    public function up(Schema $schema): void
    {
        if ($schema->hasTable('bitbag_wishlist')) {
            return;
        }

        $this->addSql('CREATE TABLE bitbag_wishlist (id SERIAL NOT NULL, shop_user_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_578D4E775F37A13B ON bitbag_wishlist (token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_578D4E77A45D93BF ON bitbag_wishlist (shop_user_id)');
        $this->addSql('CREATE TABLE bitbag_wishlist_product (id SERIAL NOT NULL, wishlist_id INT NOT NULL, product_id INT DEFAULT NULL, variant_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3DBE67A0FB8E54CD ON bitbag_wishlist_product (wishlist_id)');
        $this->addSql('CREATE INDEX IDX_3DBE67A04584665A ON bitbag_wishlist_product (product_id)');
        $this->addSql('CREATE INDEX IDX_3DBE67A03B69A9AF ON bitbag_wishlist_product (variant_id)');
        $this->addSql('ALTER TABLE bitbag_wishlist ADD CONSTRAINT FK_578D4E77A45D93BF FOREIGN KEY (shop_user_id) REFERENCES sylius_shop_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bitbag_wishlist_product ADD CONSTRAINT FK_3DBE67A0FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES bitbag_wishlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bitbag_wishlist_product ADD CONSTRAINT FK_3DBE67A04584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bitbag_wishlist_product ADD CONSTRAINT FK_3DBE67A03B69A9AF FOREIGN KEY (variant_id) REFERENCES sylius_product_variant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist_product DROP CONSTRAINT FK_3DBE67A0FB8E54CD');
        $this->addSql('DROP TABLE bitbag_wishlist');
        $this->addSql('DROP TABLE bitbag_wishlist_product');
    }
}
