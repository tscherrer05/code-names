<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200801190647 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE game_player DROP FOREIGN KEY FK_E52CD7AD99E6F5DF');
        $this->addSql('DROP INDEX IDX_E52CD7AD99E6F5DF ON game_player');
        $this->addSql('ALTER TABLE game_player DROP player_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE game_player ADD player_id INT NOT NULL');
        $this->addSql('ALTER TABLE game_player ADD CONSTRAINT FK_E52CD7AD99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E52CD7AD99E6F5DF ON game_player (player_id)');
    }
}
