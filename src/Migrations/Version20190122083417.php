<?php

declare(strict_types=1);

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190122083417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE archiver (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, configuration LONGTEXT NOT NULL, enabled TINYINT(1) NOT NULL, last_run_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edoc_case_file (id VARCHAR(255) NOT NULL, archiver_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', case_file_identifier VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_27EE0EC3A430C03C (archiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edoc_document (id VARCHAR(255) NOT NULL, archiver_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', document_identifier VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_DEA07E8DA430C03C (archiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE edoc_log_entry (id INT AUTO_INCREMENT NOT NULL, archiver_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', e_doc_case_id VARCHAR(255) NOT NULL, hearing_id VARCHAR(255) NOT NULL, reply_id VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_2136944DA430C03C (archiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ext_log_entries (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(8) NOT NULL, logged_at DATETIME NOT NULL, object_id VARCHAR(64) DEFAULT NULL, object_class VARCHAR(255) NOT NULL, version INT NOT NULL, data LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', username VARCHAR(255) DEFAULT NULL, INDEX log_class_lookup_idx (object_class), INDEX log_date_lookup_idx (logged_at), INDEX log_user_lookup_idx (username), INDEX log_version_lookup_idx (object_id, object_class, version), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE edoc_case_file ADD CONSTRAINT FK_27EE0EC3A430C03C FOREIGN KEY (archiver_id) REFERENCES archiver (id)');
        $this->addSql('ALTER TABLE edoc_document ADD CONSTRAINT FK_DEA07E8DA430C03C FOREIGN KEY (archiver_id) REFERENCES archiver (id)');
        $this->addSql('ALTER TABLE edoc_log_entry ADD CONSTRAINT FK_2136944DA430C03C FOREIGN KEY (archiver_id) REFERENCES archiver (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edoc_case_file DROP FOREIGN KEY FK_27EE0EC3A430C03C');
        $this->addSql('ALTER TABLE edoc_document DROP FOREIGN KEY FK_DEA07E8DA430C03C');
        $this->addSql('ALTER TABLE edoc_log_entry DROP FOREIGN KEY FK_2136944DA430C03C');
        $this->addSql('DROP TABLE archiver');
        $this->addSql('DROP TABLE edoc_case_file');
        $this->addSql('DROP TABLE edoc_document');
        $this->addSql('DROP TABLE edoc_log_entry');
        $this->addSql('DROP TABLE ext_log_entries');
    }
}
