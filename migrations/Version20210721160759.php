<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210721160759 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, external_id VARCHAR(127) NOT NULL, name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_64C19C19F75D7B0 (external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE position (id INT AUTO_INCREMENT NOT NULL, application_id INT NOT NULL, category_id INT NOT NULL, `index` INT DEFAULT NULL, rating_type VARCHAR(255) DEFAULT NULL, total_quantity VARCHAR(255) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, locale VARCHAR(31) NOT NULL, country VARCHAR(31) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_462CE4F53E030ACD (application_id), INDEX IDX_462CE4F512469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F53E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F512469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE application ADD category_id INT DEFAULT NULL, CHANGE url url VARCHAR(2047) NOT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC112469DE2 ON application (category_id)');
        $this->addSql(file_get_contents('dump/categories.sql', false));
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC112469DE2');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F512469DE2');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP INDEX IDX_A45BDDC112469DE2 ON application');
        $this->addSql('ALTER TABLE application DROP category_id, CHANGE url url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
