<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200508203530 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TRIGGER delete_game_player BEFORE DELETE ON `sessions`
        FOR EACH ROW BEGIN
          DELETE FROM game_player WHERE session_id = OLD.sess_id;
        END;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TRIGGER delete_game_player;');
    }
}
