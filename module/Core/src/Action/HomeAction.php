<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function getenv;
use function htmlspecialchars;

readonly class HomeAction implements RequestHandlerInterface, StatusCodeInterface
{
    private const DEFAULT_DASHBOARD_URL = 'https://app.shlink.io/';
    private const DOCUMENTATION_URL = 'https://shlink.io/documentation/';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $configuredDashboardUrl = getenv('SHLINK_WEB_CLIENT_URL');
        $dashboardUrl = htmlspecialchars(
            $configuredDashboardUrl === false || $configuredDashboardUrl === ''
                ? self::DEFAULT_DASHBOARD_URL
                : $configuredDashboardUrl,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
        $documentationUrl = htmlspecialchars(self::DOCUMENTATION_URL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return new Response(
            self::STATUS_OK,
            [
                'Content-Type' => 'text/html; charset=utf-8',
                'Cache-Control' => 'public, max-age=300',
            ],
            <<<HTML
            <!doctype html>
            <html lang="ar" dir="rtl">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="description" content="أنشئ روابط قصيرة ورموز QR قابلة للتتبع من مكان واحد.">
                <title>روابطك، أبسط وأذكى</title>
                <style>
                    :root { color-scheme: light; --ink: #102a43; --muted: #52606d; --paper: #f7fafc; --white: #fff; --blue: #155eef; --cyan: #10b9a8; --line: #d9e2ec; --shadow: 0 20px 50px rgba(16, 42, 67, .10); }
                    * { box-sizing: border-box; }
                    html { scroll-behavior: smooth; }
                    body { margin: 0; background: var(--paper); color: var(--ink); font-family: Tahoma, Arial, sans-serif; line-height: 1.7; }
                    a { color: inherit; text-decoration: none; }
                    .shell { width: min(1120px, calc(100% - 40px)); margin: auto; }
                    .topbar { background: var(--white); border-bottom: 1px solid var(--line); }
                    .nav { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
                    .brand { display: inline-flex; align-items: center; gap: 11px; font-size: 1.1rem; font-weight: 800; letter-spacing: -.2px; }
                    .brand-mark { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 11px; color: #fff; background: linear-gradient(135deg, var(--blue), #7147dd); font-size: .86rem; direction: ltr; }
                    .nav-link { color: var(--muted); font-size: .94rem; font-weight: 700; }
                    .hero { position: relative; overflow: hidden; padding: 86px 0 78px; background: radial-gradient(circle at 12% 12%, rgba(16,185,168,.18), transparent 27%), radial-gradient(circle at 82% 18%, rgba(21,94,239,.16), transparent 28%), var(--paper); }
                    .hero-grid { display: grid; grid-template-columns: 1.12fr .88fr; align-items: center; gap: 64px; }
                    .eyebrow { display: inline-flex; gap: 8px; align-items: center; padding: 7px 12px; border: 1px solid #b7e8e2; border-radius: 99px; color: #087c70; background: #ecfdf9; font-size: .83rem; font-weight: 800; }
                    h1 { max-width: 660px; margin: 18px 0 18px; font-size: clamp(2.25rem, 6vw, 4.5rem); line-height: 1.13; letter-spacing: -1.8px; }
                    h1 em { color: var(--blue); font-style: normal; }
                    .lead { max-width: 610px; margin: 0; color: var(--muted); font-size: 1.1rem; }
                    .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
                    .button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 21px; border-radius: 10px; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease; }
                    .button:hover { transform: translateY(-2px); }
                    .button-primary { color: #fff; background: var(--blue); box-shadow: 0 10px 22px rgba(21,94,239,.25); }
                    .button-secondary { color: var(--ink); border: 1px solid var(--line); background: var(--white); }
                    .trust { display: flex; flex-wrap: wrap; gap: 17px; margin-top: 26px; color: var(--muted); font-size: .86rem; font-weight: 700; }
                    .trust span::before { content: '✓'; display: inline-grid; place-items: center; width: 18px; height: 18px; margin-left: 6px; border-radius: 50%; background: #d9f7f2; color: #087c70; font-weight: 900; }
                    .preview { position: relative; padding: 25px; border: 1px solid rgba(255,255,255,.75); border-radius: 25px; background: rgba(255,255,255,.82); box-shadow: var(--shadow); backdrop-filter: blur(12px); }
                    .preview-title { margin: 0 0 17px; font-size: .88rem; color: var(--muted); font-weight: 800; }
                    .short-card { padding: 19px; border: 1px solid var(--line); border-radius: 16px; background: #fff; }
                    .short-url { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: var(--blue); font-weight: 900; direction: ltr; text-align: left; }
                    .copy { padding: 5px 8px; border-radius: 6px; background: #eef4ff; color: var(--blue); font-size: .76rem; direction: rtl; }
                    .destination { overflow: hidden; margin: 11px 0 0; color: var(--muted); font-size: .82rem; white-space: nowrap; text-overflow: ellipsis; direction: ltr; text-align: left; }
                    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 13px; }
                    .stat { padding: 12px 9px; border-radius: 12px; background: #f8fafc; text-align: center; }
                    .stat strong { display: block; color: var(--ink); font-size: 1.14rem; direction: ltr; }
                    .stat span { color: var(--muted); font-size: .72rem; }
                    .qr { display: grid; place-items: center; width: 124px; height: 124px; margin: 23px auto 0; padding: 12px; border-radius: 15px; background: #fff; box-shadow: 0 8px 22px rgba(16,42,67,.08); }
                    .qr-grid { width: 100px; height: 100px; background: repeating-linear-gradient(90deg, #102a43 0 8px, transparent 8px 12px), repeating-linear-gradient(#102a43 0 8px, transparent 8px 12px); opacity: .9; mask-image: linear-gradient(135deg,#000 60%,transparent 60%); }
                    .section { padding: 78px 0; }
                    .section-heading { max-width: 650px; margin: 0 auto 39px; text-align: center; }
                    .section-heading h2 { margin: 0 0 9px; font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.25; letter-spacing: -.7px; }
                    .section-heading p { margin: 0; color: var(--muted); }
                    .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
                    .feature { padding: 27px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); }
                    .feature-icon { display: grid; place-items: center; width: 42px; height: 42px; margin-bottom: 17px; border-radius: 12px; background: #eef4ff; color: var(--blue); font-size: 1.25rem; font-weight: 900; }
                    .feature h3 { margin: 0 0 8px; font-size: 1.08rem; }
                    .feature p { margin: 0; color: var(--muted); font-size: .93rem; }
                    .steps-wrap { background: #102a43; color: #fff; }
                    .steps-wrap .section-heading p { color: #c4d5e6; }
                    .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; counter-reset: step; }
                    .step { position: relative; padding: 26px 26px 26px 20px; border-right: 1px solid rgba(255,255,255,.2); }
                    .step:last-child { border: 0; }
                    .step::before { counter-increment: step; content: '0' counter(step); display: block; margin-bottom: 15px; color: #77e6d9; font-size: .86rem; font-weight: 900; letter-spacing: 1px; direction: ltr; }
                    .step h3 { margin: 0 0 7px; font-size: 1.1rem; }
                    .step p { margin: 0; color: #c4d5e6; font-size: .92rem; }
                    .cta { padding: 68px 0; text-align: center; }
                    .cta-box { padding: 49px 25px; border-radius: 22px; background: linear-gradient(135deg, #155eef, #4c2ba4); color: #fff; }
                    .cta h2 { margin: 0 0 9px; font-size: clamp(1.7rem, 4vw, 2.45rem); }
                    .cta p { max-width: 570px; margin: 0 auto 24px; color: #e8eeff; }
                    .cta .button { color: var(--blue); background: #fff; }
                    footer { padding: 25px 0 31px; border-top: 1px solid var(--line); color: var(--muted); font-size: .84rem; }
                    .footer-content { display: flex; justify-content: space-between; gap: 20px; }
                    @media (max-width: 800px) { .hero { padding-top: 60px; } .hero-grid { grid-template-columns: 1fr; gap: 42px; } .preview { max-width: 460px; width: 100%; margin: auto; } .features, .steps { grid-template-columns: 1fr; } .step { border-right: 0; border-bottom: 1px solid rgba(255,255,255,.2); } .step:last-child { border-bottom: 0; } }
                    @media (max-width: 480px) { .shell { width: min(100% - 30px, 1120px); } .nav-link { display: none; } h1 { letter-spacing: -1px; } .actions .button { width: 100%; } .trust { display: grid; gap: 8px; } .footer-content { display: grid; } }
                </style>
            </head>
            <body>
                <header class="topbar">
                    <nav class="nav shell" aria-label="التنقل الرئيسي">
                        <a class="brand" href="/" aria-label="الصفحة الرئيسية"><span class="brand-mark">QR</span><span>رابطك الذكي</span></a>
                        <a class="nav-link" href="#how">كيف تبدأ؟</a>
                    </nav>
                </header>
                <main>
                    <section class="hero">
                        <div class="shell hero-grid">
                            <div>
                                <span class="eyebrow">روابط قصيرة · رموز QR · تحليلات واضحة</span>
                                <h1>كل رابط يستحق أن يكون <em>أسهل وأذكى.</em></h1>
                                <p class="lead">حوّل الروابط الطويلة إلى روابط قصيرة احترافية، أنشئ رمز QR قابلًا للمشاركة، وتعرّف إلى أداء حملاتك من لوحة تحكم واحدة.</p>
                                <div class="actions">
                                    <a class="button button-primary" href="{$dashboardUrl}" target="_blank" rel="noopener noreferrer">ابدأ من لوحة التحكم</a>
                                    <a class="button button-secondary" href="#how">اكتشف طريقة الاستخدام</a>
                                </div>
                                <div class="trust"><span>روابط قابلة للتخصيص</span><span>رموز QR للمشاركة</span><span>إحصاءات للزيارات</span></div>
                            </div>
                            <div class="preview" aria-label="معاينة لرابط مختصر وتحليلاته">
                                <p class="preview-title">معاينة سريعة لنتيجتك</p>
                                <div class="short-card">
                                    <div class="short-url"><span>your-domain.com/sale</span><span class="copy">رابطك القصير</span></div>
                                    <p class="destination">https://example.com/your-long-campaign-link</p>
                                    <div class="stats"><div class="stat"><strong>2,840</strong><span>زيارة</span></div><div class="stat"><strong>61%</strong><span>جوال</span></div><div class="stat"><strong>18</strong><span>دولة</span></div></div>
                                </div>
                                <div class="qr" aria-hidden="true"><div class="qr-grid"></div></div>
                            </div>
                        </div>
                    </section>
                    <section class="section" aria-labelledby="features-title">
                        <div class="shell">
                            <div class="section-heading"><h2 id="features-title">أدوات بسيطة، أثر قابل للقياس</h2><p>استخدم المنصة لتسهيل الوصول إلى محتواك وقراءة نتيجة كل مشاركة بثقة.</p></div>
                            <div class="features">
                                <article class="feature"><div class="feature-icon">↗</div><h3>اختصر وخصّص الروابط</h3><p>امنح روابطك عنوانًا سهل التذكّر وملائمًا للحملات والرسائل ومنشورات التواصل.</p></article>
                                <article class="feature"><div class="feature-icon">▦</div><h3>أنشئ رمز QR جاهزًا للمشاركة</h3><p>حوّل رابطك المختصر إلى رمز قابل للمسح لاستخدامه في المطبوعات والواجهات والفعاليات.</p></article>
                                <article class="feature"><div class="feature-icon">⌁</div><h3>افهم أداء روابطك</h3><p>تابع الزيارات ومصادرها ومواقعها لتعرف القنوات والمحتوى الأكثر فاعلية.</p></article>
                            </div>
                        </div>
                    </section>
                    <section class="steps-wrap section" id="how" aria-labelledby="steps-title">
                        <div class="shell">
                            <div class="section-heading"><h2 id="steps-title">ثلاث خطوات للبدء</h2><p>أنشئ رابطك وشاركه ثم تابع أثره، من دون تعقيد.</p></div>
                            <div class="steps">
                                <article class="step"><h3>أضف وجهتك</h3><p>انسخ الرابط الذي تريد مشاركته وأنشئ له رابطًا قصيرًا من لوحة التحكم.</p></article>
                                <article class="step"><h3>شارك بالطريقة التي تناسبك</h3><p>استخدم الرابط في حملتك أو حمّل رمز QR وضعه في المادة المطبوعة أو الرقمية.</p></article>
                                <article class="step"><h3>راجع النتائج</h3><p>راقب زيارات الرابط لتطوير محتواك وتحديد القنوات التي تحقق أفضل استجابة.</p></article>
                            </div>
                        </div>
                    </section>
                    <section class="cta">
                        <div class="shell"><div class="cta-box"><h2>اجعل كل مشاركة بداية قابلة للقياس</h2><p>افتح لوحة التحكم الآن وأنشئ أول رابط قصير أو رمز QR لحملتك القادمة.</p><a class="button" href="{$dashboardUrl}" target="_blank" rel="noopener noreferrer">فتح لوحة التحكم</a></div></div>
                    </section>
                </main>
                <footer><div class="shell footer-content"><span>رابطك الذكي — إدارة روابط أبسط.</span><a href="{$documentationUrl}" target="_blank" rel="noopener noreferrer">دليل الاستخدام</a></div></footer>
            </body>
            </html>
            HTML,
        );
    }
}
