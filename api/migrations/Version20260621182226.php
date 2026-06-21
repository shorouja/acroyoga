<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621182226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(150) NOT NULL, abbreviation VARCHAR(20) DEFAULT NULL, difficulty VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE TABLE exercise_skill (exercise_id INTEGER NOT NULL, skill_id INTEGER NOT NULL, PRIMARY KEY (exercise_id, skill_id), CONSTRAINT FK_7B0B13B5E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7B0B13B55585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7B0B13B5E934951A ON exercise_skill (exercise_id)');
        $this->addSql('CREATE INDEX IDX_7B0B13B55585C142 ON exercise_skill (skill_id)');
        $this->addSql('CREATE TABLE exercise_group (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE TABLE exercise_group_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, position INTEGER NOT NULL, group_id INTEGER NOT NULL, exercise_id INTEGER NOT NULL, CONSTRAINT FK_7630A20EFE54D947 FOREIGN KEY (group_id) REFERENCES exercise_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7630A20EE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7630A20EFE54D947 ON exercise_group_item (group_id)');
        $this->addSql('CREATE INDEX IDX_7630A20EE934951A ON exercise_group_item (exercise_id)');
        $this->addSql('CREATE TABLE partnership (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, partner_alias VARCHAR(100) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user1_id INTEGER NOT NULL, user2_id INTEGER DEFAULT NULL, CONSTRAINT FK_8619D6AE56AE248B FOREIGN KEY (user1_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8619D6AE441B8B65 FOREIGN KEY (user2_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8619D6AE56AE248B ON partnership (user1_id)');
        $this->addSql('CREATE INDEX IDX_8619D6AE441B8B65 ON partnership (user2_id)');
        $this->addSql('CREATE TABLE partnership_progress (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, role VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, mastered_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, partnership_id INTEGER NOT NULL, exercise_id INTEGER NOT NULL, CONSTRAINT FK_DA7ABF1B6AE7F85 FOREIGN KEY (partnership_id) REFERENCES partnership (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DA7ABF1BE934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DA7ABF1B6AE7F85 ON partnership_progress (partnership_id)');
        $this->addSql('CREATE INDEX IDX_DA7ABF1BE934951A ON partnership_progress (exercise_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DA7ABF1B6AE7F85E934951A57698A6A ON partnership_progress (partnership_id, exercise_id, role)');
        $this->addSql('CREATE TABLE session_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_date DATE NOT NULL, notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, partnership_id INTEGER NOT NULL, CONSTRAINT FK_F2E6F0FF6AE7F85 FOREIGN KEY (partnership_id) REFERENCES partnership (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F2E6F0FF6AE7F85 ON session_log (partnership_id)');
        $this->addSql('CREATE TABLE skill (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, abbreviation VARCHAR(20) DEFAULT NULL, category VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, display_name VARCHAR(100) DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE user_exercise_progress (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, role VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, mastered_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, user_id INTEGER NOT NULL, exercise_id INTEGER NOT NULL, CONSTRAINT FK_DC72E985A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_DC72E985E934951A FOREIGN KEY (exercise_id) REFERENCES exercise (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DC72E985A76ED395 ON user_exercise_progress (user_id)');
        $this->addSql('CREATE INDEX IDX_DC72E985E934951A ON user_exercise_progress (exercise_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DC72E985A76ED395E934951A57698A6A ON user_exercise_progress (user_id, exercise_id, role)');
        $this->addSql('CREATE TABLE user_skill_level (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, level VARCHAR(255) NOT NULL, achieved_at DATETIME NOT NULL, user_id INTEGER NOT NULL, skill_id INTEGER NOT NULL, CONSTRAINT FK_16D996BAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_16D996BA5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_16D996BAA76ED395 ON user_skill_level (user_id)');
        $this->addSql('CREATE INDEX IDX_16D996BA5585C142 ON user_skill_level (skill_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_16D996BAA76ED3955585C142 ON user_skill_level (user_id, skill_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE exercise');
        $this->addSql('DROP TABLE exercise_skill');
        $this->addSql('DROP TABLE exercise_group');
        $this->addSql('DROP TABLE exercise_group_item');
        $this->addSql('DROP TABLE partnership');
        $this->addSql('DROP TABLE partnership_progress');
        $this->addSql('DROP TABLE session_log');
        $this->addSql('DROP TABLE skill');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_exercise_progress');
        $this->addSql('DROP TABLE user_skill_level');
    }
}
