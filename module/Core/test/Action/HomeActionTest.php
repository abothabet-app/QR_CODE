<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Action;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Action\HomeAction;

class HomeActionTest extends TestCase
{
    #[Test]
    public function returnsAnArabicLandingPage(): void
    {
        $response = (new HomeAction())->handle(ServerRequestFactory::fromGlobals());
        $body = $response->getBody()->__toString();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<html lang="ar" dir="rtl">', $body);
        self::assertStringContainsString('أبونابت للبرمجيات · روابط ذكية بأثر واضح', $body);
        self::assertStringContainsString('حلول روابط ذكية، <em>بهوية رقمية أقوى.</em>', $body);
        self::assertStringContainsString('/assets/abunabit-logo.webp', $body);
        self::assertStringContainsString('ابدأ من لوحة التحكم', $body);
        self::assertStringContainsString('كيف تبدأ؟', $body);
    }
}
