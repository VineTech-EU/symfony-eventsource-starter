<?php

declare(strict_types=1);

namespace App\SharedKernel\Adapters\Console;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test:minio',
    description: 'Test MinIO S3 storage integration',
)]
final class TestMinIOCommand extends Command
{
    public function __construct(
        private readonly FilesystemOperator $minioStorage,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Testing MinIO S3 Storage Integration');

            // Test write
            $io->section('Writing test file...');
            $testContent = 'Hello from MinIO! Test at ' . date('Y-m-d H:i:s');
            $this->minioStorage->write('test.txt', $testContent);
            $io->success('File written successfully!');

            // Test read
            $io->section('Reading test file...');
            $contents = $this->minioStorage->read('test.txt');
            $io->writeln("File contents: {$contents}");
            $io->success('File read successfully!');

            // Test list
            $io->section('Listing files in bucket...');
            $fileCount = 0;
            foreach ($this->minioStorage->listContents('/') as $file) {
                ++$fileCount;
                if ($file instanceof FileAttributes) {
                    $io->writeln(\sprintf(
                        '  - %s (%d bytes)',
                        $file->path(),
                        $file->fileSize()
                    ));
                } else {
                    $io->writeln(\sprintf('  - %s (directory)', $file->path()));
                }
            }
            $io->info("Total files: {$fileCount}");

            // Test delete
            $io->section('Deleting test file...');
            $this->minioStorage->delete('test.txt');
            $io->success('File deleted successfully!');

            $io->success('MinIO integration working correctly!');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('MinIO test failed: ' . $e->getMessage());
            $io->writeln('Exception: ' . $e::class);

            return Command::FAILURE;
        }
    }
}
