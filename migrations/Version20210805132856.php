<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210805132856 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_store_application (id INT AUTO_INCREMENT NOT NULL, publisher_id INT DEFAULT NULL, category_id INT NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, url TEXT NOT NULL, icon VARCHAR(255) NOT NULL, version VARCHAR(255) NOT NULL, i_phone TINYINT(1) NOT NULL, i_pad TINYINT(1) NOT NULL, reason LONGTEXT DEFAULT NULL, purchases TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', UNIQUE INDEX UNIQ_DB8B07C09F75D7B0 (external_id), INDEX IDX_DB8B07C040C86FCE (publisher_id), INDEX IDX_DB8B07C012469DE2 (category_id), INDEX IDX_DB8B07C09F75D7B05E237E0640C86FCE12469DE2 (external_id, name, publisher_id, category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE app_store_category (id INT AUTO_INCREMENT NOT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', UNIQUE INDEX UNIQ_E7C1F4C59F75D7B0 (external_id), INDEX IDX_E7C1F4C59F75D7B05E237E06 (external_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE app_store_position (id INT AUTO_INCREMENT NOT NULL, application_id INT NOT NULL, category_id INT DEFAULT NULL, rating_type VARCHAR(255) NOT NULL, country VARCHAR(31) NOT NULL, prev_index INT DEFAULT NULL, curr_index INT DEFAULT NULL, total_quantity INT NOT NULL, reason VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_A7A109F13E030ACD (application_id), INDEX IDX_A7A109F112469DE2 (category_id), INDEX IDX_A7A109F15373C966 (country), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE app_store_publisher (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, external_id VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, url VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, number VARCHAR(1024) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', UNIQUE INDEX UNIQ_9B649CB29F75D7B0 (external_id), INDEX IDX_9B649CB2A76ED395 (user_id), INDEX IDX_9B649CB29F75D7B05E237E06 (external_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_bin` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE app_store_application ADD CONSTRAINT FK_DB8B07C040C86FCE FOREIGN KEY (publisher_id) REFERENCES app_store_publisher (id)');
        $this->addSql('ALTER TABLE app_store_application ADD CONSTRAINT FK_DB8B07C012469DE2 FOREIGN KEY (category_id) REFERENCES app_store_category (id)');
        $this->addSql('ALTER TABLE app_store_position ADD CONSTRAINT FK_A7A109F13E030ACD FOREIGN KEY (application_id) REFERENCES app_store_application (id)');
        $this->addSql('ALTER TABLE app_store_position ADD CONSTRAINT FK_A7A109F112469DE2 FOREIGN KEY (category_id) REFERENCES app_store_category (id)');
        $this->addSql('ALTER TABLE app_store_publisher ADD CONSTRAINT FK_9B649CB2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_store_position DROP FOREIGN KEY FK_A7A109F13E030ACD');
        $this->addSql('ALTER TABLE app_store_application DROP FOREIGN KEY FK_DB8B07C012469DE2');
        $this->addSql('ALTER TABLE app_store_position DROP FOREIGN KEY FK_A7A109F112469DE2');
        $this->addSql('ALTER TABLE app_store_application DROP FOREIGN KEY FK_DB8B07C040C86FCE');
        $this->addSql('DROP TABLE app_store_application');
        $this->addSql('DROP TABLE app_store_category');
        $this->addSql('DROP TABLE app_store_position');
        $this->addSql('DROP TABLE app_store_publisher');
    }
}
