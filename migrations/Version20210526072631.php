<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210526072631 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publisher ADD number VARCHAR(1024) DEFAULT NULL AFTER `ip`, DROP description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publisher ADD description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, DROP number');
    }
}
