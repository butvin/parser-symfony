<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210331151443 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, publisher_id INT NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, icon VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_A45BDDC140C86FCE (publisher_id), UNIQUE INDEX UNIQ_A45BDDC140C86FCE9F75D7B0 (publisher_id, external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publisher (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, url VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_9CE8D546A76ED395 (user_id), UNIQUE INDEX UNIQ_9CE8D546F47645AE (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id)');
        $this->addSql('ALTER TABLE publisher ADD CONSTRAINT FK_9CE8D546A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC140C86FCE');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE publisher');
    }
}
