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

final class Version20210428130553 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Add missing constraint to bitbag_wishlist_product (PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist_product DROP CONSTRAINT FK_3DBE67A0FB8E54CD');
        $this->addSql('ALTER TABLE bitbag_wishlist_product ADD CONSTRAINT FK_3DBE67A0FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES bitbag_wishlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist_product DROP CONSTRAINT FK_3DBE67A0FB8E54CD');
        $this->addSql('ALTER TABLE bitbag_wishlist_product ADD CONSTRAINT FK_3DBE67A0FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES bitbag_wishlist (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
