<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Publisher;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210406144029 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publisher ADD type VARCHAR(75) NOT NULL AFTER `user_id`');
        $this->addSql('UPDATE publisher SET `type` = :type', ['type' => Publisher::TYPE_PLAY_STORE]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publisher DROP type');
    }
}
