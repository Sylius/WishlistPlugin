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

final class Version20231015123539 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Adding timestampable columns to track creating and updating a wishlist (PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('bitbag_wishlist')->hasColumn('created_at')) {
            return;
        }

        $this->addSql('ALTER TABLE bitbag_wishlist ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE bitbag_wishlist ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE bitbag_wishlist SET created_at = NOW()');
        $this->addSql('ALTER TABLE bitbag_wishlist ALTER COLUMN created_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bitbag_wishlist DROP created_at');
        $this->addSql('ALTER TABLE bitbag_wishlist DROP updated_at');
    }
}
