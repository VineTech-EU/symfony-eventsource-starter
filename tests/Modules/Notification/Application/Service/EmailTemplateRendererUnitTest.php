<?php

declare(strict_types=1);

namespace App\Tests\Modules\Notification\Application\Service;

use App\Modules\Notification\Application\Service\EmailTemplateRenderer;
use App\Modules\Notification\Domain\ValueObject\RenderedEmail;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;

/**
 * @covers \App\Modules\Notification\Application\Service\EmailTemplateRenderer
 *
 * @internal
 */
final class EmailTemplateRendererUnitTest extends TestCase
{
    private Environment&MockObject $twig;
    private EmailTemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->renderer = new EmailTemplateRenderer($this->twig);
    }

    public function testRenderReturnsRenderedEmailWithHtmlAndTextVersions(): void
    {
        // Arrange
        $htmlTemplate = '@user_emails/user/welcome.html.twig';
        $textTemplate = '@user_emails/user/welcome.txt.twig';
        $context = ['name' => 'John Doe'];

        $expectedHtml = '<html><body>Welcome John Doe!</body></html>';
        $expectedText = 'Welcome John Doe!';

        $this->twig
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template, array $ctx) use ($htmlTemplate, $textTemplate, $context, $expectedHtml, $expectedText): string {
                if ($template === $htmlTemplate && $ctx === $context) {
                    return $expectedHtml;
                }

                if ($template === $textTemplate && $ctx === $context) {
                    return $expectedText;
                }

                throw new \RuntimeException('Unexpected template or context');
            })
        ;

        // Act
        $rendered = $this->renderer->render($htmlTemplate, $context);

        // Assert
        self::assertInstanceOf(RenderedEmail::class, $rendered);
        self::assertSame($expectedHtml, $rendered->html);
        self::assertSame($expectedText, $rendered->text);
    }

    public function testRenderFallsBackToStrippingHtmlWhenTextTemplateNotFound(): void
    {
        // Arrange
        $htmlTemplate = '@user_emails/user/notification.html.twig';
        $textTemplate = '@user_emails/user/notification.txt.twig';
        $context = ['message' => 'Hello'];

        $expectedHtml = '<html><body><p>Hello World</p></body></html>';

        $this->twig
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template) use ($htmlTemplate, $textTemplate, $expectedHtml): string {
                if ($template === $htmlTemplate) {
                    return $expectedHtml;
                }

                if ($template === $textTemplate) {
                    throw new LoaderError('Template not found');
                }

                throw new \RuntimeException('Unexpected template');
            })
        ;

        // Act
        $rendered = $this->renderer->render($htmlTemplate, $context);

        // Assert
        self::assertInstanceOf(RenderedEmail::class, $rendered);
        self::assertSame($expectedHtml, $rendered->html);
        self::assertSame('Hello World', $rendered->text); // HTML tags stripped, whitespace collapsed
    }

    public function testRenderStripsHtmlAndCollapsesWhitespace(): void
    {
        // Arrange
        $htmlTemplate = 'email/complex.html.twig';
        $textTemplate = 'email/complex.txt.twig';

        $htmlWithWhitespace = <<<'HTML'
            <html>
              <body>
                <h1>Welcome</h1>
                <p>This is   a    message</p>
                <p>Another paragraph</p>
              </body>
            </html>
            HTML;

        $this->twig
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template) use ($htmlTemplate, $textTemplate, $htmlWithWhitespace): string {
                if ($template === $htmlTemplate) {
                    return $htmlWithWhitespace;
                }

                if ($template === $textTemplate) {
                    throw new LoaderError('Template not found');
                }

                throw new \RuntimeException('Unexpected template');
            })
        ;

        // Act
        $rendered = $this->renderer->render($htmlTemplate);

        // Assert
        // Whitespace should be collapsed to single spaces
        self::assertStringContainsString('Welcome', $rendered->text);
        self::assertStringContainsString('This is a message', $rendered->text);
        self::assertStringNotContainsString('<html>', $rendered->text);
        self::assertStringNotContainsString('<body>', $rendered->text);
        self::assertStringNotContainsString('<p>', $rendered->text);
    }

    public function testRenderPassesContextToTwigTemplates(): void
    {
        // Arrange
        $htmlTemplate = 'email/test.html.twig';
        $textTemplate = 'email/test.txt.twig';
        $context = [
            'username' => 'alice',
            'email' => 'alice@example.com',
            'date' => '2024-01-01',
        ];

        $this->twig
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template, array $ctx) use ($htmlTemplate, $textTemplate, $context): string {
                // Verify context is passed correctly
                self::assertSame($context, $ctx);

                if ($template === $htmlTemplate) {
                    return '<html>HTML</html>';
                }

                if ($template === $textTemplate) {
                    return 'TEXT';
                }

                throw new \RuntimeException('Unexpected template');
            })
        ;

        // Act
        $this->renderer->render($htmlTemplate, $context);

        // Assert - expectations verified by mock
    }

    public function testRenderWithEmptyContext(): void
    {
        // Arrange
        $htmlTemplate = 'email/simple.html.twig';
        $textTemplate = 'email/simple.txt.twig';

        $this->twig
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnCallback(static function (string $template, array $ctx) use ($htmlTemplate, $textTemplate): string {
                // Verify empty array is passed
                self::assertSame([], $ctx);

                if ($template === $htmlTemplate) {
                    return '<html>Simple</html>';
                }

                if ($template === $textTemplate) {
                    return 'Simple';
                }

                throw new \RuntimeException('Unexpected template');
            })
        ;

        // Act
        $rendered = $this->renderer->render($htmlTemplate);

        // Assert
        self::assertSame('<html>Simple</html>', $rendered->html);
        self::assertSame('Simple', $rendered->text);
    }
}
