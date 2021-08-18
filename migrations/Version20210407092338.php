<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210407092338 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC140C86FCE');
        $this->addSql('ALTER TABLE application ADD url VARCHAR(255) NOT NULL AFTER `external_id`');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_9CE8D546F47645AE ON publisher');
        $this->addSql('ALTER TABLE publisher ADD external_id VARCHAR(255) DEFAULT NULL AFTER `type`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9CE8D5469F75D7B0 ON publisher (external_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC140C86FCE');
        $this->addSql('ALTER TABLE application DROP url');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id)');
        $this->addSql('DROP INDEX UNIQ_9CE8D5469F75D7B0 ON publisher');
        $this->addSql('ALTER TABLE publisher DROP external_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9CE8D546F47645AE ON publisher (url)');
    }
}
