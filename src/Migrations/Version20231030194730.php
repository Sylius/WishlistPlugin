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
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

final class Version20231030194730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds token_idx and channel_shop_user_token_idx indexes to database';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('bitbag_wishlist')->hasIndex('token_idx')) {
            return;
        }

        $this->addSql('CREATE INDEX token_idx ON bitbag_wishlist (token)');
        $this->addSql('CREATE INDEX channel_shop_user_token_idx ON bitbag_wishlist (channel_id, shop_user_id, token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX token_idx ON bitbag_wishlist');
        $this->addSql('DROP INDEX channel_shop_user_token_idx ON bitbag_wishlist');
    }
}
