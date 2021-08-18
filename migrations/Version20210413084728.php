<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210413084728 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application ADD reason LONGTEXT DEFAULT NULL AFTER `purchases`');
        $this->addSql('ALTER TABLE publisher ADD reason LONGTEXT DEFAULT NULL AFTER `name`');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application DROP reason');
        $this->addSql('ALTER TABLE publisher DROP reason');
    }
}
