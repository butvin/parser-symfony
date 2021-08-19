<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210818233052 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, publisher_id INT NOT NULL, category_id INT DEFAULT NULL, external_id VARCHAR(255) NOT NULL, url VARCHAR(2047) NOT NULL, icon VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, version VARCHAR(127) NOT NULL, purchases TINYINT(1) NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_A45BDDC140C86FCE (publisher_id), INDEX IDX_A45BDDC112469DE2 (category_id), UNIQUE INDEX UNIQ_A45BDDC140C86FCE9F75D7B0 (publisher_id, external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, external_id VARCHAR(127) NOT NULL, name VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_64C19C19F75D7B0 (external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE position (id INT AUTO_INCREMENT NOT NULL, application_id INT NOT NULL, category_id INT NOT NULL, `index` INT DEFAULT NULL, `prev_index` INT DEFAULT NULL, rating_type VARCHAR(255) DEFAULT NULL, total_quantity VARCHAR(255) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, locale VARCHAR(31) NOT NULL, country VARCHAR(31) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_462CE4F53E030ACD (application_id), INDEX IDX_462CE4F512469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE publisher (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, type VARCHAR(75) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, number VARCHAR(1024) DEFAULT NULL, url VARCHAR(1024) NOT NULL, name VARCHAR(1024) DEFAULT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', INDEX IDX_9CE8D546A76ED395 (user_id), UNIQUE INDEX UNIQ_9CE8D5469F75D7B0 (external_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', date_of_birth DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', firstname VARCHAR(64) DEFAULT NULL, lastname VARCHAR(64) DEFAULT NULL, website VARCHAR(64) DEFAULT NULL, biography VARCHAR(1000) DEFAULT NULL, gender VARCHAR(1) DEFAULT NULL, locale VARCHAR(8) DEFAULT NULL, timezone VARCHAR(64) DEFAULT NULL, phone VARCHAR(64) DEFAULT NULL, facebook_uid VARCHAR(255) DEFAULT NULL, facebook_name VARCHAR(255) DEFAULT NULL, facebook_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', twitter_uid VARCHAR(255) DEFAULT NULL, twitter_name VARCHAR(255) DEFAULT NULL, twitter_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', gplus_uid VARCHAR(255) DEFAULT NULL, gplus_name VARCHAR(255) DEFAULT NULL, gplus_data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', token VARCHAR(255) DEFAULT NULL, two_step_code VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_8D93D649A0D96FBF (email_canonical), UNIQUE INDEX UNIQ_8D93D649C05FB297 (confirmation_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fos_user_user_group (user_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_B3C77447A76ED395 (user_id), INDEX IDX_B3C77447FE54D947 (group_id), PRIMARY KEY(user_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_8F02BF9D5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC140C86FCE FOREIGN KEY (publisher_id) REFERENCES publisher (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F53E030ACD FOREIGN KEY (application_id) REFERENCES application (id)');
        $this->addSql('ALTER TABLE position ADD CONSTRAINT FK_462CE4F512469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE publisher ADD CONSTRAINT FK_9CE8D546A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE fos_user_user_group ADD CONSTRAINT FK_B3C77447A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fos_user_user_group ADD CONSTRAINT FK_B3C77447FE54D947 FOREIGN KEY (group_id) REFERENCES user_group (id) ON DELETE CASCADE');
        $this->addSql(file_get_contents('dump/categories.sql', false));
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F53E030ACD');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC112469DE2');
        $this->addSql('ALTER TABLE position DROP FOREIGN KEY FK_462CE4F512469DE2');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC140C86FCE');
        $this->addSql('ALTER TABLE publisher DROP FOREIGN KEY FK_9CE8D546A76ED395');
        $this->addSql('ALTER TABLE fos_user_user_group DROP FOREIGN KEY FK_B3C77447A76ED395');
        $this->addSql('ALTER TABLE fos_user_user_group DROP FOREIGN KEY FK_B3C77447FE54D947');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE position');
        $this->addSql('DROP TABLE publisher');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE fos_user_user_group');
        $this->addSql('DROP TABLE user_group');
    }
}
