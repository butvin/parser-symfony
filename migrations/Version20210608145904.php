<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210608145904 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\'');
    }
}
