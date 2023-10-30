<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231030222504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "user" ADD grade_book_number VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD subject VARCHAR(60) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ALTER type TYPE VARCHAR(255)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6492519DBBE ON "user" (grade_book_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649FBCE3E7A ON "user" (subject)');
        $this->addSql("UPDATE \"user\" SET grade_book_number = 'ЗК00' || id WHERE type = 'student'");
        $this->addSql("UPDATE \"user\" SET subject = 'Новый Предмет №' || id WHERE type = 'teacher'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D6492519DBBE');
        $this->addSql('DROP INDEX UNIQ_8D93D649FBCE3E7A');
        $this->addSql('ALTER TABLE "user" DROP grade_book_number');
        $this->addSql('ALTER TABLE "user" DROP subject');
        $this->addSql('ALTER TABLE "user" ALTER type TYPE VARCHAR(10)');
    }
}
