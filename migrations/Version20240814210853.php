<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240814210853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, player1_id_id INT DEFAULT NULL, player2_id_id INT DEFAULT NULL, winner_id_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_232B318CB1F5D3AD (player1_id_id), INDEX IDX_232B318C801DC930 (player2_id_id), INDEX IDX_232B318CFC53D4E9 (winner_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE move (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, player_id INT NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_EF3E3778E48FD905 (game_id), INDEX IDX_EF3E377899E6F5DF (player_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CB1F5D3AD FOREIGN KEY (player1_id_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C801DC930 FOREIGN KEY (player2_id_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CFC53D4E9 FOREIGN KEY (winner_id_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E3778E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE move ADD CONSTRAINT FK_EF3E377899E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD losses INT DEFAULT 0 NOT NULL, DROP loses, CHANGE wins wins INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CB1F5D3AD');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C801DC930');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CFC53D4E9');
        $this->addSql('ALTER TABLE move DROP FOREIGN KEY FK_EF3E3778E48FD905');
        $this->addSql('ALTER TABLE move DROP FOREIGN KEY FK_EF3E377899E6F5DF');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE move');
        $this->addSql('DROP INDEX UNIQ_8D93D649F85E0677 ON `user`');
        $this->addSql('ALTER TABLE `user` ADD loses INT DEFAULT NULL, DROP losses, CHANGE wins wins INT DEFAULT NULL');
    }
}
