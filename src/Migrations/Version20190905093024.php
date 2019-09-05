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

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190905093024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edoc_case_file ADD share_file_item_stream_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE edoc_document ADD share_file_item_stream_id VARCHAR(255) NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        // Fill in share_file_item_stream_id.
        // The item with newest `updated_at` will be representative for the ShareFile item.
        $tableNames = ['edoc_case_file', 'edoc_document'];
        foreach ($tableNames as $tableName) {
            $sql = 'SELECT * FROM '.$tableName.';';
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            $stuff = [];
            while (false !== $record = $statement->fetch(FetchMode::STANDARD_OBJECT)) {
                $data = json_decode($record->data);
                $streamId = $data->sharefile->StreamID;
                if (!isset($stuff[$record->archiver_id][$streamId])
                    || $stuff[$record->archiver_id][$streamId]->updated_at < $record->updated_at) {
                    $stuff[$record->archiver_id][$streamId] = $record;
                }
            }

            foreach ($stuff as $archiverId => $items) {
                foreach ($items as $streamId => $item) {
                    $this->connection->executeUpdate(
                        'UPDATE '.$tableName.' SET share_file_item_stream_id = :share_file_item_stream_id WHERE share_file_item_id = :share_file_item_id',
                        [
                            'share_file_item_stream_id' => $streamId,
                            'share_file_item_id' => $item->share_file_item_id,
                        ]
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE edoc_case_file DROP share_file_item_stream_id');
        $this->addSql('ALTER TABLE edoc_document DROP share_file_item_stream_id');
    }
}
