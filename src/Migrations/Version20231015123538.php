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
use Doctrine\Migrations\AbstractMigration;

final class Version20231015123538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adding timestampable columns to track creating and updating a wishlist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE bitbag_wishlist SET created_at =  NOW()');
        $this->addSql('ALTER TABLE bitbag_wishlist CHANGE COLUMN created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist DROP created_at, DROP updated_at');
    }
}
