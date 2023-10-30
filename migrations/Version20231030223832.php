<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231030223832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE student_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE teacher_id_seq CASCADE');
        $this->addSql('ALTER TABLE student DROP login');
        $this->addSql('ALTER TABLE student DROP created_at');
        $this->addSql('ALTER TABLE student DROP updated_at');
        $this->addSql('ALTER TABLE student DROP type');
        $this->addSql('ALTER TABLE student ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33BF396750 FOREIGN KEY (id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE teacher DROP login');
        $this->addSql('ALTER TABLE teacher DROP created_at');
        $this->addSql('ALTER TABLE teacher DROP updated_at');
        $this->addSql('ALTER TABLE teacher DROP type');
        $this->addSql('ALTER TABLE teacher ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE teacher ADD CONSTRAINT FK_B0F6A6D5BF396750 FOREIGN KEY (id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX uniq_8d93d649fbce3e7a');
        $this->addSql('DROP INDEX uniq_8d93d6492519dbbe');
        $this->addSql("INSERT INTO student SELECT id, grade_book_number FROM \"user\" WHERE type = 'student'");
        $this->addSql("INSERT INTO teacher SELECT id, subject FROM \"user\" WHERE type = 'teacher'");
        $this->addSql('ALTER TABLE "user" DROP grade_book_number');
        $this->addSql('ALTER TABLE "user" DROP subject');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE student_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE teacher_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE teacher DROP CONSTRAINT FK_B0F6A6D5BF396750');
        $this->addSql('ALTER TABLE teacher ADD login VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE teacher ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE teacher ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE teacher ADD type VARCHAR(10) NOT NULL');
        $this->addSql('CREATE SEQUENCE teacher_id_seq');
        $this->addSql('SELECT setval(\'teacher_id_seq\', (SELECT MAX(id) FROM teacher))');
        $this->addSql('ALTER TABLE teacher ALTER id SET DEFAULT nextval(\'teacher_id_seq\')');
        $this->addSql('ALTER TABLE student DROP CONSTRAINT FK_B723AF33BF396750');
        $this->addSql('ALTER TABLE student ADD login VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE student ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE student ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE student ADD type VARCHAR(10) NOT NULL');
        $this->addSql('CREATE SEQUENCE student_id_seq');
        $this->addSql('SELECT setval(\'student_id_seq\', (SELECT MAX(id) FROM student))');
        $this->addSql('ALTER TABLE student ALTER id SET DEFAULT nextval(\'student_id_seq\')');
        $this->addSql('ALTER TABLE "user" ADD grade_book_number VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD subject VARCHAR(60) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d649fbce3e7a ON "user" (subject)');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6492519dbbe ON "user" (grade_book_number)');
    }
}
