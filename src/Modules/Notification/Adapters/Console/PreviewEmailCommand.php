<?php

declare(strict_types=1);

namespace App\Modules\Notification\Adapters\Console;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Preview Email Command.
 *
 * Renders email templates for development/review without sending them.
 * Useful for designers to preview emails during development.
 *
 * Usage:
 * bin/console email:preview welcome --name="John Doe"
 * bin/console email:preview admin-notification --name="Jane" --email="jane@example.com"
 * bin/console email:preview approval --name="Bob" --format=text
 * bin/console email:preview all --output-dir=/tmp/emails
 */
#[AsCommand(
    name: 'email:preview',
    description: 'Preview email templates without sending them'
)]
final class PreviewEmailCommand extends Command
{
    private const TEMPLATES = [
        'welcome' => [
            'template' => '@user_emails/user/welcome.html.twig',
            'defaults' => ['name' => 'John Doe'],
        ],
        'admin-notification' => [
            'template' => '@user_emails/user/admin_notification.html.twig',
            'defaults' => ['newUserName' => 'Jane Smith', 'newUserEmail' => 'jane@example.com'],
        ],
        'approval' => [
            'template' => '@user_emails/user/approval_confirmation.html.twig',
            'defaults' => ['name' => 'Bob Johnson'],
        ],
    ];

    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('template', InputArgument::REQUIRED, 'Email template name (welcome, admin-notification, approval, or "all")')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Recipient name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Recipient email')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (html or text)', 'html')
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'Save to files in this directory')
            ->setHelp(
                <<<'HELP'
                    Preview email templates without sending them.

                    Examples:
                      <info>bin/console email:preview welcome</info>
                      <info>bin/console email:preview welcome --name="John Doe"</info>
                      <info>bin/console email:preview admin-notification --email="admin@example.com"</info>
                      <info>bin/console email:preview approval --format=text</info>
                      <info>bin/console email:preview all --output-dir=/tmp/emails</info>
                    HELP
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $templateName */
        $templateName = $input->getArgument('template');

        /** @var string $format */
        $format = $input->getOption('format');

        /** @var null|string $outputDir */
        $outputDir = $input->getOption('output-dir');

        if ('all' === $templateName) {
            return $this->previewAll($io, $format, $outputDir);
        }

        if (!isset(self::TEMPLATES[$templateName])) {
            $io->error(\sprintf('Unknown template "%s". Available: %s, or "all"', $templateName, implode(', ', array_keys(self::TEMPLATES))));

            return Command::FAILURE;
        }

        $config = self::TEMPLATES[$templateName];
        $context = $this->buildContext($input, $config['defaults']);

        try {
            $this->previewTemplate($io, $templateName, $config['template'], $context, $format, $outputDir);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to render template: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @param array<string, mixed> $defaults
     *
     * @return array<string, mixed>
     */
    private function buildContext(InputInterface $input, array $defaults): array
    {
        $context = $defaults;

        $name = $input->getOption('name');
        if (\is_string($name) && '' !== $name) {
            $context['name'] = $name;
            $context['newUserName'] = $name; // For admin notification
        }

        $email = $input->getOption('email');
        if (\is_string($email) && '' !== $email) {
            $context['newUserEmail'] = $email;
        }

        return $context;
    }

    private function previewAll(SymfonyStyle $io, string $format, ?string $outputDir): int
    {
        $io->title('Previewing all email templates');

        foreach (self::TEMPLATES as $name => $config) {
            $io->section("Template: {$name}");
            $this->previewTemplate($io, $name, $config['template'], $config['defaults'], $format, $outputDir);
        }

        $io->success('All templates previewed successfully');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function previewTemplate(
        SymfonyStyle $io,
        string $name,
        string $template,
        array $context,
        string $format,
        ?string $outputDir
    ): void {
        $rendered = $this->renderer->render($template, $context);

        $content = ('text' === $format) ? $rendered->text : $rendered->html;

        if (null !== $outputDir) {
            $this->saveToFile($io, $name, $content, $format, $outputDir);
        } else {
            $io->writeln($content);
        }

        $io->success(\sprintf('Template "%s" rendered successfully (%s format)', $name, $format));
    }

    private function saveToFile(SymfonyStyle $io, string $name, string $content, string $format, string $outputDir): void
    {
        if (!is_dir($outputDir) && !mkdir($outputDir, 0o755, true) && !is_dir($outputDir)) {
            throw new \RuntimeException(\sprintf('Directory "%s" was not created', $outputDir));
        }

        $extension = 'html' === $format ? 'html' : 'txt';
        $filename = \sprintf('%s/%s.%s', $outputDir, $name, $extension);

        file_put_contents($filename, $content);

        $io->writeln(\sprintf('  <info>→</info> Saved to: %s', $filename));
    }
}
