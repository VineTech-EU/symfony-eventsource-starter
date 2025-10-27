<?php

declare(strict_types=1);

namespace App\Modules\Notification\Application\Service;

use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use Twig\Environment;
use Twig\Error\LoaderError;

/**
 * Email Template Renderer.
 *
 * Responsible for rendering Twig email templates (HTML + text).
 * Separates template rendering logic from email sending logic.
 *
 * Usage:
 * $rendered = $renderer->render('email/user/welcome.html.twig', ['name' => 'John']);
 * $email->html($rendered->html)->text($rendered->text);
 *
 * Note: Not marked as 'final' to allow mocking in unit tests.
 */
readonly class EmailTemplateRenderer
{
    public function __construct(
        private Environment $twig,
    ) {}

    /**
     * Render an email template in both HTML and text formats.
     *
     * @param string               $htmlTemplate Path to HTML template (e.g., 'email/user/welcome.html.twig')
     * @param array<string, mixed> $context      Variables to pass to template
     *
     * @return RenderedEmail Rendered email with HTML and text versions
     */
    public function render(string $htmlTemplate, array $context = []): RenderedEmail
    {
        // Render HTML version
        $html = $this->twig->render($htmlTemplate, $context);

        // Determine text template path (replace .html.twig with .txt.twig)
        $textTemplate = str_replace('.html.twig', '.txt.twig', $htmlTemplate);

        // Render text version (fallback to stripping HTML tags if txt template doesn't exist)
        try {
            $text = $this->twig->render($textTemplate, $context);
        } catch (LoaderError) {
            // Fallback: strip HTML tags for text version
            $text = strip_tags($html);
            $text = (string) preg_replace('/\s+/', ' ', $text); // Collapse whitespace
            $text = trim($text);
        }

        return new RenderedEmail($html, $text);
    }
}
