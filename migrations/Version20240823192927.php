<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240823192927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C801DC930');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CB1F5D3AD');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CFC53D4E9');
        $this->addSql('DROP INDEX IDX_232B318CB1F5D3AD ON game');
        $this->addSql('DROP INDEX IDX_232B318C801DC930 ON game');
        $this->addSql('DROP INDEX IDX_232B318CFC53D4E9 ON game');
        $this->addSql('ALTER TABLE game ADD player1_id INT DEFAULT NULL, ADD player2_id INT DEFAULT NULL, ADD winner_id INT DEFAULT NULL, DROP player1_id_id, DROP player2_id_id, DROP winner_id_id');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CC0990423 FOREIGN KEY (player1_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CD22CABCD FOREIGN KEY (player2_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_232B318CC0990423 ON game (player1_id)');
        $this->addSql('CREATE INDEX IDX_232B318CD22CABCD ON game (player2_id)');
        $this->addSql('CREATE INDEX IDX_232B318C5DFCD4B8 ON game (winner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CC0990423');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CD22CABCD');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C5DFCD4B8');
        $this->addSql('DROP INDEX IDX_232B318CC0990423 ON game');
        $this->addSql('DROP INDEX IDX_232B318CD22CABCD ON game');
        $this->addSql('DROP INDEX IDX_232B318C5DFCD4B8 ON game');
        $this->addSql('ALTER TABLE game ADD player1_id_id INT DEFAULT NULL, ADD player2_id_id INT DEFAULT NULL, ADD winner_id_id INT DEFAULT NULL, DROP player1_id, DROP player2_id, DROP winner_id');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C801DC930 FOREIGN KEY (player2_id_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CB1F5D3AD FOREIGN KEY (player1_id_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CFC53D4E9 FOREIGN KEY (winner_id_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_232B318CB1F5D3AD ON game (player1_id_id)');
        $this->addSql('CREATE INDEX IDX_232B318C801DC930 ON game (player2_id_id)');
        $this->addSql('CREATE INDEX IDX_232B318CFC53D4E9 ON game (winner_id_id)');
    }
}
