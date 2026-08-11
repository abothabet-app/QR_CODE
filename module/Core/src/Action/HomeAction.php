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
                <meta name="description" content="أبونابت للبرمجيات: روابط قصيرة ورموز QR وتحليلات ذكية بهوية رقمية متكاملة.">
                <meta name="theme-color" content="#101d31">
                <title>أبونابت للبرمجيات | روابط QR ذكية</title>
                <style>
                    :root { color-scheme: light; --navy: #101d31; --navy-soft: #173149; --teal: #52c9c0; --teal-deep: #237d88; --gold: #eac375; --ivory: #fbf7ed; --paper: #f4f7f6; --white: #fff; --ink: #13253a; --muted: #5c6d7e; --line: #d9e4e4; --shadow: 0 22px 55px rgba(16,29,49,.14); }
                    * { box-sizing: border-box; }
                    html { scroll-behavior: smooth; }
                    body { margin: 0; background: var(--paper); color: var(--ink); font-family: Tahoma, Arial, sans-serif; line-height: 1.7; }
                    a { color: inherit; text-decoration: none; }
                    .shell { width: min(1120px, calc(100% - 40px)); margin: auto; }
                    .topbar { background: var(--navy); border-bottom: 1px solid rgba(82,201,192,.22); }
                    .nav { min-height: 84px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
                    .brand { display: inline-flex; align-items: center; gap: 12px; color: var(--ivory); }
                    .brand-logo { width: 54px; height: 54px; border-radius: 14px; object-fit: cover; box-shadow: 0 6px 15px rgba(0,0,0,.18); }
                    .brand-copy { display: grid; line-height: 1.15; }
                    .brand-copy strong { color: var(--ivory); font-size: 1.08rem; }
                    .brand-copy small { margin-top: 4px; color: var(--teal); font-size: .74rem; font-weight: 700; }
                    .nav-link { padding: 8px 0; border-bottom: 2px solid transparent; color: #d8e4e6; font-size: .94rem; font-weight: 700; transition: color .18s ease, border-color .18s ease; }
                    .nav-link:hover { border-color: var(--gold); color: var(--gold); }
                    .hero { position: relative; overflow: hidden; padding: 88px 0 80px; background: radial-gradient(circle at 10% 12%, rgba(82,201,192,.23), transparent 27%), radial-gradient(circle at 82% 80%, rgba(234,195,117,.16), transparent 28%), var(--navy); color: var(--ivory); }
                    .hero::after { content: ''; position: absolute; width: 420px; height: 420px; top: -205px; left: -95px; border: 1px solid rgba(82,201,192,.23); border-radius: 50%; box-shadow: 0 0 0 46px rgba(82,201,192,.04), 0 0 0 93px rgba(82,201,192,.035); }
                    .hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: 1.12fr .88fr; align-items: center; gap: 64px; }
                    .eyebrow { display: inline-flex; gap: 8px; align-items: center; padding: 7px 13px; border: 1px solid rgba(82,201,192,.45); border-radius: 99px; color: var(--teal); background: rgba(82,201,192,.10); font-size: .83rem; font-weight: 800; }
                    h1 { max-width: 660px; margin: 18px 0; font-size: clamp(2.3rem, 6vw, 4.5rem); line-height: 1.12; letter-spacing: -1.8px; }
                    h1 em { color: var(--gold); font-style: normal; }
                    .lead { max-width: 610px; margin: 0; color: #d7e3e6; font-size: 1.1rem; }
                    .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
                    .button { display: inline-flex; align-items: center; justify-content: center; min-height: 49px; padding: 0 22px; border-radius: 11px; font-weight: 800; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
                    .button:hover { transform: translateY(-2px); }
                    .button-primary { color: var(--navy); background: var(--teal); box-shadow: 0 12px 23px rgba(82,201,192,.20); }
                    .button-primary:hover { background: #6cddd4; }
                    .button-secondary { border: 1px solid rgba(251,247,237,.36); color: var(--ivory); background: rgba(255,255,255,.04); }
                    .button-secondary:hover { border-color: var(--gold); color: var(--gold); }
                    .trust { display: flex; flex-wrap: wrap; gap: 17px; margin-top: 27px; color: #c8d8da; font-size: .86rem; font-weight: 700; }
                    .trust span::before { content: '✓'; display: inline-grid; place-items: center; width: 18px; height: 18px; margin-left: 6px; border-radius: 50%; color: var(--navy); background: var(--gold); font-weight: 900; }
                    .preview { position: relative; padding: 25px; border: 1px solid rgba(251,247,237,.24); border-radius: 25px; background: rgba(251,247,237,.96); color: var(--ink); box-shadow: var(--shadow); }
                    .preview::before { content: ''; position: absolute; top: 15px; left: 19px; width: 11px; height: 11px; border-radius: 50%; background: var(--gold); box-shadow: 19px 0 0 var(--teal), 38px 0 0 var(--navy-soft); }
                    .preview-title { margin: 12px 0 17px; color: var(--teal-deep); font-size: .88rem; font-weight: 900; }
                    .short-card { padding: 19px; border: 1px solid var(--line); border-radius: 16px; background: var(--white); }
                    .short-url { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: var(--teal-deep); font-weight: 900; direction: ltr; text-align: left; }
                    .copy { padding: 5px 8px; border-radius: 6px; background: #e4f7f4; color: var(--teal-deep); font-size: .76rem; direction: rtl; }
                    .destination { overflow: hidden; margin: 11px 0 0; color: var(--muted); font-size: .82rem; white-space: nowrap; text-overflow: ellipsis; direction: ltr; text-align: left; }
                    .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 13px; }
                    .stat { padding: 12px 9px; border-radius: 12px; background: #f3f7f6; text-align: center; }
                    .stat strong { display: block; color: var(--navy); font-size: 1.14rem; direction: ltr; }
                    .stat span { color: var(--muted); font-size: .72rem; }
                    .qr { display: grid; place-items: center; width: 124px; height: 124px; margin: 23px auto 0; padding: 12px; border-radius: 15px; background: var(--ivory); box-shadow: 0 8px 22px rgba(16,29,49,.08); }
                    .qr-grid { width: 100px; height: 100px; background: repeating-linear-gradient(90deg, var(--navy) 0 8px, transparent 8px 12px), repeating-linear-gradient(var(--navy) 0 8px, transparent 8px 12px); opacity: .92; mask-image: linear-gradient(135deg,#000 60%,transparent 60%); }
                    .section { padding: 78px 0; }
                    .section-heading { max-width: 650px; margin: 0 auto 39px; text-align: center; }
                    .section-heading h2 { margin: 0 0 9px; color: var(--navy); font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.25; letter-spacing: -.7px; }
                    .section-heading p { margin: 0; color: var(--muted); }
                    .features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
                    .feature { padding: 28px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); box-shadow: 0 10px 25px rgba(16,29,49,.035); transition: transform .18s ease, box-shadow .18s ease; }
                    .feature:hover { transform: translateY(-4px); box-shadow: var(--shadow); }
                    .feature-icon { display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 17px; border-radius: 12px; color: var(--navy); background: var(--gold); font-size: 1.25rem; font-weight: 900; }
                    .feature h3 { margin: 0 0 8px; color: var(--navy); font-size: 1.08rem; }
                    .feature p { margin: 0; color: var(--muted); font-size: .93rem; }
                    .steps-wrap { background: linear-gradient(135deg, var(--navy), var(--navy-soft)); color: var(--ivory); }
                    .steps-wrap .section-heading h2 { color: var(--ivory); }
                    .steps-wrap .section-heading p { color: #c7d9dc; }
                    .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; counter-reset: step; }
                    .step { position: relative; padding: 26px 26px 26px 20px; border-right: 1px solid rgba(82,201,192,.29); }
                    .step:last-child { border: 0; }
                    .step::before { counter-increment: step; content: '0' counter(step); display: block; margin-bottom: 15px; color: var(--gold); font-size: .86rem; font-weight: 900; letter-spacing: 1px; direction: ltr; }
                    .step h3 { margin: 0 0 7px; color: var(--teal); font-size: 1.1rem; }
                    .step p { margin: 0; color: #c7d9dc; font-size: .92rem; }
                    .cta { padding: 68px 0; text-align: center; }
                    .cta-box { position: relative; overflow: hidden; padding: 50px 25px; border-radius: 22px; background: linear-gradient(135deg, var(--teal-deep), var(--teal)); color: var(--navy); }
                    .cta-box::after { content: ''; position: absolute; width: 190px; height: 190px; top: -110px; left: -50px; border: 22px solid rgba(234,195,117,.35); border-radius: 50%; }
                    .cta-box > * { position: relative; z-index: 1; }
                    .cta h2 { margin: 0 0 9px; font-size: clamp(1.7rem, 4vw, 2.45rem); }
                    .cta p { max-width: 570px; margin: 0 auto 24px; color: #173149; }
                    .cta .button { color: var(--ivory); background: var(--navy); box-shadow: 0 10px 22px rgba(16,29,49,.20); }
                    .cta .button:hover { background: #1d3853; }
                    footer { padding: 27px 0 33px; background: var(--navy); color: #c7d9dc; font-size: .84rem; }
                    .footer-content { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
                    .footer-brand { display: inline-flex; align-items: center; gap: 9px; color: var(--ivory); font-weight: 800; }
                    .footer-brand span { color: var(--teal); font-size: .75rem; font-weight: 700; }
                    footer a { color: var(--gold); font-weight: 800; }
                    @media (max-width: 800px) { .hero { padding-top: 60px; } .hero-grid { grid-template-columns: 1fr; gap: 42px; } .preview { max-width: 460px; width: 100%; margin: auto; } .features, .steps { grid-template-columns: 1fr; } .step { border-right: 0; border-bottom: 1px solid rgba(82,201,192,.29); } .step:last-child { border-bottom: 0; } }
                    @media (max-width: 480px) { .shell { width: min(100% - 30px, 1120px); } .nav-link { display: none; } .brand-logo { width: 48px; height: 48px; } h1 { letter-spacing: -1px; } .actions .button { width: 100%; } .trust { display: grid; gap: 8px; } .footer-content { align-items: flex-start; display: grid; } }
                </style>
            </head>
            <body>
                <header class="topbar">
                    <nav class="nav shell" aria-label="التنقل الرئيسي">
                        <a class="brand" href="/" aria-label="أبونابت للبرمجيات — الصفحة الرئيسية">
                            <img class="brand-logo" src="/assets/abunabit-logo.webp" width="54" height="54" alt="شعار أبونابت للبرمجيات">
                            <span class="brand-copy"><strong>أبونابت</strong><small>للبرمجيات</small></span>
                        </a>
                        <a class="nav-link" href="#how">كيف تبدأ؟</a>
                    </nav>
                </header>
                <main>
                    <section class="hero">
                        <div class="shell hero-grid">
                            <div>
                                <span class="eyebrow">أبونابت للبرمجيات · روابط ذكية بأثر واضح</span>
                                <h1>حلول روابط ذكية، <em>بهوية رقمية أقوى.</em></h1>
                                <p class="lead">مع أبونابت للبرمجيات، حوّل الروابط الطويلة إلى روابط احترافية، أنشئ رمز QR قابلًا للمشاركة، وتابع أثر حملاتك من لوحة تحكم واحدة.</p>
                                <div class="actions">
                                    <a class="button button-primary" href="{$dashboardUrl}" target="_blank" rel="noopener noreferrer">ابدأ من لوحة التحكم</a>
                                    <a class="button button-secondary" href="#how">اكتشف طريقة الاستخدام</a>
                                </div>
                                <div class="trust"><span>روابط قابلة للتخصيص</span><span>رموز QR للمشاركة</span><span>تحليلات للزيارات</span></div>
                            </div>
                            <div class="preview" aria-label="معاينة لرابط مختصر وتحليلاته">
                                <p class="preview-title">نظرة سريعة على أداء حملتك</p>
                                <div class="short-card">
                                    <div class="short-url"><span>your-domain.com/launch</span><span class="copy">رابط أبونابت القصير</span></div>
                                    <p class="destination">https://example.com/your-campaign-destination</p>
                                    <div class="stats"><div class="stat"><strong>2,840</strong><span>زيارة</span></div><div class="stat"><strong>61%</strong><span>جوال</span></div><div class="stat"><strong>18</strong><span>دولة</span></div></div>
                                </div>
                                <div class="qr" aria-hidden="true"><div class="qr-grid"></div></div>
                            </div>
                        </div>
                    </section>
                    <section class="section" aria-labelledby="features-title">
                        <div class="shell">
                            <div class="section-heading"><h2 id="features-title">أدواتك للوصول بثقة</h2><p>صممت أبونابت هذه الأدوات لتجعل كل مشاركة منظمة وسهلة القياس وأكثر أثرًا.</p></div>
                            <div class="features">
                                <article class="feature"><div class="feature-icon">↗</div><h3>روابط بهوية علامتك</h3><p>اختصر روابطك بعناوين سهلة التذكّر ومتسقة مع حملاتك ورسائلك ومنصاتك الرقمية.</p></article>
                                <article class="feature"><div class="feature-icon">▦</div><h3>رموز QR جاهزة للمشاركة</h3><p>أنشئ رمزًا قابلًا للمسح لرابطك واستخدمه في المطبوعات والواجهات والفعاليات بكل سهولة.</p></article>
                                <article class="feature"><div class="feature-icon">⌁</div><h3>قرارات مبنية على البيانات</h3><p>تابع الزيارات ومصادرها ومواقعها لتعرف المحتوى والقنوات التي تحقق أفضل استجابة.</p></article>
                            </div>
                        </div>
                    </section>
                    <section class="steps-wrap section" id="how" aria-labelledby="steps-title">
                        <div class="shell">
                            <div class="section-heading"><h2 id="steps-title">خطوات بسيطة، نتائج واضحة</h2><p>أنشئ رابطك وشاركه ثم راقب أثره؛ كل ذلك من بيئة واحدة.</p></div>
                            <div class="steps">
                                <article class="step"><h3>أضف وجهتك</h3><p>انسخ الرابط الذي تريد مشاركته وأنشئ له رابطًا قصيرًا مميزًا من لوحة التحكم.</p></article>
                                <article class="step"><h3>شارك بثقة</h3><p>استخدم الرابط في حملتك أو حمّل رمز QR وضعه في المادة المطبوعة أو الرقمية.</p></article>
                                <article class="step"><h3>طوّر أداءك</h3><p>راجع الزيارات باستمرار لتفهم جمهورك وتحدد القنوات التي تحقق أفضل استجابة.</p></article>
                            </div>
                        </div>
                    </section>
                    <section class="cta">
                        <div class="shell"><div class="cta-box"><h2>ابدأ رحلة رقمية أكثر ذكاءً</h2><p>أنشئ أول رابط قصير أو رمز QR لحملتك القادمة مع أبونابت للبرمجيات.</p><a class="button" href="{$dashboardUrl}" target="_blank" rel="noopener noreferrer">فتح لوحة التحكم</a></div></div>
                    </section>
                </main>
                <footer><div class="shell footer-content"><span class="footer-brand">أبونابت <span>للبرمجيات</span></span><span>حلول رقمية تربطك بجمهورك.</span><a href="{$documentationUrl}" target="_blank" rel="noopener noreferrer">دليل الاستخدام</a></div></footer>
            </body>
            </html>
            HTML,
        );
    }
}
