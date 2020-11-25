<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125193953 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE point (id INT AUTO_INCREMENT NOT NULL, workout_id INT NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longtitude DOUBLE PRECISION DEFAULT NULL, altitude DOUBLE PRECISION DEFAULT NULL, distance DOUBLE PRECISION DEFAULT NULL, heart_rate INT DEFAULT NULL, speed DOUBLE PRECISION DEFAULT NULL, time DATE NOT NULL, INDEX IDX_B7A5F324A6CCCFC9 (workout_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workout (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, distance DOUBLE PRECISION DEFAULT NULL, calories INT NOT NULL, duration INT NOT NULL, start DATE NOT NULL, avg_heart_rate INT DEFAULT NULL, max_heart_rate INT DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, avg_speed DOUBLE PRECISION DEFAULT NULL, max_speed DOUBLE PRECISION DEFAULT NULL, duration_active INT DEFAULT NULL, steps INT DEFAULT NULL, UNIQUE INDEX UNIQ_649FFB72C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE point ADD CONSTRAINT FK_B7A5F324A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id)');
        $this->addSql('ALTER TABLE workout ADD CONSTRAINT FK_649FFB72C54C8C93 FOREIGN KEY (type_id) REFERENCES type (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workout DROP FOREIGN KEY FK_649FFB72C54C8C93');
        $this->addSql('ALTER TABLE point DROP FOREIGN KEY FK_B7A5F324A6CCCFC9');
        $this->addSql('DROP TABLE point');
        $this->addSql('DROP TABLE type');
        $this->addSql('DROP TABLE workout');
    }
}
