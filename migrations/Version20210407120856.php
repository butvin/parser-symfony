<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210407120856 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application ADD purchases TINYINT(1) NOT NULL AFTER `icon`');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application DROP purchases');
    }
}
