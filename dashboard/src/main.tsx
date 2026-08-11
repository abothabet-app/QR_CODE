import { FormEvent, useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

type ShortUrl = {
  shortCode: string;
  shortUrl: string;
  longUrl: string;
  title?: string | null;
  dateCreated?: string;
  tags?: string[];
  visitsSummary?: { visitsCount?: number };
};

type Paginator = {
  data?: ShortUrl[];
  pagination?: { currentPage?: number; totalItems?: number; totalPages?: number };
};

type SummaryResponse = {
  shortUrls?: { shortUrls?: Paginator };
  visits?: { visits?: Record<string, unknown> };
};

type Session = { authenticated: boolean; configured: boolean; serviceUrl: string };
type Domain = { domain: string; isDefault?: boolean };
type Tag = { tag: string; shortUrlsCount?: number };

type Notice = { kind: 'success' | 'error'; message: string } | null;

const navItems = [
  { id: 'overview', label: 'نظرة عامة', icon: '◫' },
  { id: 'links', label: 'الروابط', icon: '↗' },
  { id: 'qr', label: 'رموز QR', icon: '▦' },
  { id: 'analytics', label: 'التحليلات', icon: '⌁' },
  { id: 'settings', label: 'الإعدادات', icon: '⚙' },
] as const;

type Section = (typeof navItems)[number]['id'];

function formatNumber(value: unknown): string {
  return typeof value === 'number' ? new Intl.NumberFormat('ar').format(value) : '—';
}

function formatDate(value?: string): string {
  if (!value) return 'غير محدد';
  const date = new Date(value);
  return Number.isNaN(date.valueOf()) ? value : new Intl.DateTimeFormat('ar-SA', { dateStyle: 'medium' }).format(date);
}

function qrUrl(link: ShortUrl): string {
  const base = link.shortUrl.replace(/\/$/, '');
  return `${base}/qr-code?size=500&format=svg&margin=16&errorCorrection=Q&color=101D31&bgColor=FBF7ED`;
}

async function api<T>(url: string, init?: RequestInit): Promise<T> {
  const response = await fetch(url, {
    ...init,
    headers: { 'Content-Type': 'application/json', ...(init?.headers ?? {}) },
  });
  if (response.status === 204) return undefined as T;
  const data = (await response.json()) as T & { message?: string };
  if (!response.ok) throw new Error(data.message ?? 'تعذر تنفيذ الطلب.');
  return data;
}

function Login({ configured, onLogin }: { configured: boolean; onLogin: () => Promise<void> }) {
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function submit(event: FormEvent) {
    event.preventDefault();
    setBusy(true);
    setError('');
    try {
      await api('/api/session', { method: 'POST', body: JSON.stringify({ password }) });
      await onLogin();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'تعذر تسجيل الدخول.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="login-page">
      <section className="login-card">
        <img className="login-logo" src="/abunabit-logo.webp" alt="شعار أبونابت للبرمجيات" />
        <p className="eyebrow">أبونابت للبرمجيات</p>
        <h1>لوحة إدارة الروابط</h1>
        <p className="login-copy">أدر روابطك ورموز QR وإحصاءات الزيارات من تجربة عربية موحدة.</p>
        {!configured ? (
          <div className="setup-alert">
            <strong>أكمل إعداد الخدمة أولًا</strong>
            <span>أضف المتغيرات السرية <code>ADMIN_PASSWORD</code> و<code>SESSION_SECRET</code> و<code>SHLINK_API_KEY</code> في إعدادات النشر.</span>
          </div>
        ) : (
          <form onSubmit={submit}>
            <label htmlFor="password">كلمة مرور لوحة الإدارة</label>
            <input id="password" type="password" autoComplete="current-password" value={password} onChange={(event) => setPassword(event.target.value)} placeholder="أدخل كلمة المرور" required />
            {error && <p className="form-error">{error}</p>}
            <button className="primary-button full" disabled={busy}>{busy ? 'جارٍ الدخول…' : 'تسجيل الدخول'}</button>
          </form>
        )}
      </section>
    </main>
  );
}

function Brand() {
  return <div className="brand"><img src="/abunabit-logo.webp" alt="" /><span><strong>أبونابت</strong><small>للبرمجيات</small></span></div>;
}

function App() {
  const [session, setSession] = useState<Session | null>(null);
  const [section, setSection] = useState<Section>('overview');
  const [summary, setSummary] = useState<SummaryResponse | null>(null);
  const [links, setLinks] = useState<ShortUrl[]>([]);
  const [pagination, setPagination] = useState<Paginator['pagination']>();
  const [domains, setDomains] = useState<Domain[]>([]);
  const [tags, setTags] = useState<Tag[]>([]);
  const [loading, setLoading] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [notice, setNotice] = useState<Notice>(null);
  const [search, setSearch] = useState('');
  const [creating, setCreating] = useState(false);

  const visitMetrics = useMemo(() => {
    const raw = summary?.visits?.visits ?? {};
    return {
      total: raw.visitsCount ?? raw.totalVisits ?? raw.total,
      nonOrphan: raw.nonOrphanVisits ?? raw.nonOrphanVisitsCount,
      orphan: raw.orphanVisits ?? raw.orphanVisitsCount,
    };
  }, [summary]);

  async function refreshSession() {
    const next = await api<Session>('/api/session');
    setSession(next);
    return next;
  }

  async function loadDashboard(searchTerm = '') {
    setLoading(true);
    try {
      const [summaryData, linksData, domainsData, tagsData] = await Promise.all([
        api<SummaryResponse>('/api/dashboard-summary'),
        api<{ shortUrls?: Paginator }>(`/api/short-urls?itemsPerPage=100&orderBy=dateCreated&orderDir=DESC${searchTerm ? `&searchTerm=${encodeURIComponent(searchTerm)}` : ''}`),
        api<{ domains?: { data?: Domain[] } }>('/api/domains'),
        api<{ tags?: Tag[] }>('/api/tags'),
      ]);
      setSummary(summaryData);
      setDomains(domainsData.domains?.data ?? []);
      setTags(tagsData.tags ?? []);
      const page = linksData.shortUrls ?? {};
      setLinks(page.data ?? []);
      setPagination(page.pagination);
    } catch (reason) {
      setNotice({ kind: 'error', message: reason instanceof Error ? reason.message : 'تعذر تحميل بيانات لوحة الإدارة.' });
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => { void refreshSession(); }, []);
  useEffect(() => {
    if (session?.authenticated) void loadDashboard();
  }, [session?.authenticated]);

  async function logout() {
    await api('/api/session', { method: 'DELETE' });
    setSession((current) => current ? { ...current, authenticated: false } : current);
    setSummary(null);
    setLinks([]);
  }

  async function createLink(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const tags = String(form.get('tags') ?? '').split(',').map((tag) => tag.trim()).filter(Boolean);
    const maxVisitsValue = Number(form.get('maxVisits') || 0);
    const payload = {
      longUrl: String(form.get('longUrl') ?? ''),
      customSlug: String(form.get('customSlug') ?? ''),
      title: String(form.get('title') ?? ''),
      tags,
      ...(maxVisitsValue > 0 ? { maxVisits: maxVisitsValue } : {}),
    };

    setCreating(true);
    try {
      await api('/api/short-urls', { method: 'POST', body: JSON.stringify(payload) });
      setShowCreate(false);
      setNotice({ kind: 'success', message: 'تم إنشاء الرابط المختصر بنجاح.' });
      await loadDashboard(search);
    } catch (reason) {
      setNotice({ kind: 'error', message: reason instanceof Error ? reason.message : 'تعذر إنشاء الرابط.' });
    } finally {
      setCreating(false);
    }
  }

  async function copy(value: string) {
    await navigator.clipboard.writeText(value);
    setNotice({ kind: 'success', message: 'تم نسخ الرابط.' });
  }

  async function removeLink(link: ShortUrl) {
    if (!window.confirm(`هل تريد حذف الرابط ${link.shortCode} نهائيًا؟`)) return;
    try {
      await api(`/api/short-urls/${encodeURIComponent(link.shortCode)}`, { method: 'DELETE' });
      setNotice({ kind: 'success', message: 'تم حذف الرابط.' });
      await loadDashboard(search);
    } catch (reason) {
      setNotice({ kind: 'error', message: reason instanceof Error ? reason.message : 'تعذر حذف الرابط.' });
    }
  }

  if (!session) return <main className="loading-screen">جارٍ تجهيز لوحة أبونابت…</main>;
  if (!session.authenticated) return <Login configured={session.configured} onLogin={async () => { await refreshSession(); }} />;

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <Brand />
        <nav aria-label="أقسام لوحة الإدارة">
          {navItems.map((item) => <button key={item.id} className={section === item.id ? 'nav-item active' : 'nav-item'} onClick={() => setSection(item.id)}><span>{item.icon}</span>{item.label}</button>)}
        </nav>
        <div className="sidebar-footer"><span className="status-dot" /> متصل بخدمة الروابط</div>
      </aside>
      <main className="workspace">
        <header className="workspace-header">
          <div><p className="eyebrow">لوحة التحكم</p><h1>{navItems.find((item) => item.id === section)?.label}</h1></div>
          <div className="header-actions"><button className="ghost-button" onClick={() => void logout()}>تسجيل الخروج</button><button className="primary-button" onClick={() => setShowCreate(true)}>+ رابط جديد</button></div>
        </header>
        {notice && <div className={`notice ${notice.kind}`} role="status"><span>{notice.kind === 'success' ? '✓' : '!'}</span>{notice.message}<button onClick={() => setNotice(null)} aria-label="إغلاق">×</button></div>}

        {section === 'overview' && <Overview links={links} pagination={pagination} metrics={visitMetrics} loading={loading} onCopy={copy} onCreate={() => setShowCreate(true)} onNavigate={setSection} />}
        {section === 'links' && <Links links={links} loading={loading} search={search} setSearch={setSearch} onSearch={() => void loadDashboard(search)} onCopy={copy} onDelete={removeLink} onCreate={() => setShowCreate(true)} />}
        {section === 'qr' && <QrSection links={links} onCopy={copy} />}
        {section === 'analytics' && <Analytics metrics={visitMetrics} links={links} />}
        {section === 'settings' && <Settings serviceUrl={session.serviceUrl} domains={domains} tags={tags} />}
      </main>

      {showCreate && <CreateModal busy={creating} onClose={() => setShowCreate(false)} onSubmit={createLink} />}
    </div>
  );
}

function Overview({ links, pagination, metrics, loading, onCopy, onCreate, onNavigate }: { links: ShortUrl[]; pagination?: Paginator['pagination']; metrics: Record<string, unknown>; loading: boolean; onCopy: (value: string) => void; onCreate: () => void; onNavigate: (section: Section) => void }) {
  return <>
    <section className="welcome-card"><div><p>مرحبًا بك في مساحة العمل</p><h2>روابطك أصبحت أوضح وأسهل إدارة.</h2><span>أنشئ روابط مخصصة، شارك رموز QR، وافهم أثر كل حملة من مكان واحد.</span><button className="inline-link" onClick={onCreate}>إنشاء أول رابط ←</button></div><div className="orbit" aria-hidden="true"><i /><b /><em /></div></section>
    <section className="metrics-grid">
      <Metric label="إجمالي الروابط" value={formatNumber(pagination?.totalItems ?? links.length)} icon="↗" tone="teal" />
      <Metric label="إجمالي الزيارات" value={formatNumber(metrics.total)} icon="◌" tone="gold" />
      <Metric label="زيارات الروابط النشطة" value={formatNumber(metrics.nonOrphan)} icon="⌁" tone="navy" />
      <Metric label="زيارات غير مرتبطة" value={formatNumber(metrics.orphan)} icon="◫" tone="soft" />
    </section>
    <section className="panel"><div className="panel-heading"><div><h2>أحدث الروابط</h2><p>آخر الروابط التي تم إنشاؤها في خدمتك.</p></div><button className="text-button" onClick={() => onNavigate('links')}>عرض كل الروابط</button></div>{loading ? <div className="skeleton-list" /> : <LinksTable links={links.slice(0, 5)} onCopy={onCopy} compact />}</section>
  </>;
}

function Metric({ label, value, icon, tone }: { label: string; value: string; icon: string; tone: string }) {
  return <article className="metric"><span className={`metric-icon ${tone}`}>{icon}</span><div><p>{label}</p><strong>{value}</strong></div></article>;
}

function Links({ links, loading, search, setSearch, onSearch, onCopy, onDelete, onCreate }: { links: ShortUrl[]; loading: boolean; search: string; setSearch: (value: string) => void; onSearch: () => void; onCopy: (value: string) => void; onDelete: (link: ShortUrl) => void; onCreate: () => void }) {
  return <section className="panel links-panel"><div className="toolbar"><div className="search-box"><input value={search} onChange={(event) => setSearch(event.target.value)} onKeyDown={(event) => event.key === 'Enter' && onSearch()} placeholder="ابحث بالرابط أو العنوان أو الوسم" /><button onClick={onSearch}>بحث</button></div><button className="primary-button" onClick={onCreate}>+ رابط جديد</button></div>{loading ? <div className="skeleton-list" /> : <LinksTable links={links} onCopy={onCopy} onDelete={onDelete} />}</section>;
}

function LinksTable({ links, onCopy, onDelete, compact = false }: { links: ShortUrl[]; onCopy: (value: string) => void; onDelete?: (link: ShortUrl) => void; compact?: boolean }) {
  if (!links.length) return <div className="empty-state"><strong>لا توجد روابط بعد</strong><span>أنشئ رابطك الأول لتبدأ إدارة حملاتك.</span></div>;
  return <div className="table-wrap"><table><thead><tr><th>الرابط المختصر</th><th>الوجهة</th><th>الوسوم</th><th>الزيارات</th><th>تاريخ الإنشاء</th>{!compact && <th>الإجراءات</th>}</tr></thead><tbody>{links.map((link) => <tr key={link.shortCode}><td><div className="short-link"><strong>{link.title || link.shortCode}</strong><span>{link.shortUrl}</span></div></td><td><a className="destination" href={link.longUrl} target="_blank" rel="noreferrer">{link.longUrl}</a></td><td><div className="tags">{link.tags?.length ? link.tags.slice(0, 2).map((tag) => <span key={tag}>{tag}</span>) : '—'}</div></td><td>{formatNumber(link.visitsSummary?.visitsCount)}</td><td>{formatDate(link.dateCreated)}</td>{!compact && <td><div className="row-actions"><button onClick={() => onCopy(link.shortUrl)}>نسخ</button><a href={qrUrl(link)} target="_blank" rel="noreferrer">QR</a>{onDelete && <button className="danger" onClick={() => void onDelete(link)}>حذف</button>}</div></td>}</tr>)}</tbody></table></div>;
}

function QrSection({ links, onCopy }: { links: ShortUrl[]; onCopy: (value: string) => void }) {
  return <section className="panel"><div className="panel-heading"><div><h2>رموز QR</h2><p>استخدم الرموز المرتبطة بروابطك في المواد المطبوعة والحملات الرقمية.</p></div></div>{links.length ? <div className="qr-grid">{links.slice(0, 12).map((link) => <article className="qr-card" key={link.shortCode}><img src={qrUrl(link)} alt={`رمز QR للرابط ${link.shortCode}`} /><strong>{link.title || link.shortCode}</strong><span>{link.shortUrl}</span><div><button onClick={() => onCopy(link.shortUrl)}>نسخ الرابط</button><a href={qrUrl(link)} target="_blank" rel="noreferrer">فتح الرمز</a></div></article>)}</div> : <div className="empty-state"><strong>لا توجد رموز QR بعد</strong><span>أنشئ رابطًا مختصرًا ليظهر رمزه هنا تلقائيًا.</span></div>}</section>;
}

function Analytics({ metrics, links }: { metrics: Record<string, unknown>; links: ShortUrl[] }) {
  const topLinks = [...links].sort((a, b) => (b.visitsSummary?.visitsCount ?? 0) - (a.visitsSummary?.visitsCount ?? 0)).slice(0, 5);
  return <div className="analytics-layout"><section className="panel"><div className="panel-heading"><div><h2>ملخص الزيارات</h2><p>قراءة أولية لأداء روابطك الحالية.</p></div></div><div className="analytics-stats"><Metric label="الزيارات الكلية" value={formatNumber(metrics.total)} icon="◌" tone="teal" /><Metric label="زيارات الروابط" value={formatNumber(metrics.nonOrphan)} icon="↗" tone="gold" /></div></section><section className="panel"><div className="panel-heading"><div><h2>الروابط الأكثر نشاطًا</h2><p>ترتيب بحسب الزيارات المسجلة.</p></div></div>{topLinks.length ? <ol className="ranking">{topLinks.map((link, index) => <li key={link.shortCode}><b>{String(index + 1).padStart(2, '0')}</b><span><strong>{link.title || link.shortCode}</strong><small>{link.shortUrl}</small></span><em>{formatNumber(link.visitsSummary?.visitsCount)} زيارة</em></li>)}</ol> : <div className="empty-state"><strong>لا توجد بيانات كافية</strong><span>سيظهر ترتيب الأداء بعد إنشاء الروابط وزيارتها.</span></div>}</section></div>;
}

function Settings({ serviceUrl, domains, tags }: { serviceUrl: string; domains: Domain[]; tags: Tag[] }) {
  return <div className="settings-layout"><section className="panel"><div className="panel-heading"><div><h2>اتصال الخدمة</h2><p>إعدادات الربط الخاصة بلوحة أبونابت.</p></div></div><dl className="settings-list"><div><dt>خدمة الروابط</dt><dd><a href={serviceUrl} target="_blank" rel="noreferrer">{serviceUrl}</a></dd></div><div><dt>واجهة الإدارة</dt><dd>app.qrcode.abothabet.com</dd></div><div><dt>حالة المفتاح</dt><dd><span className="secure-badge">محفوظ بأمان داخل الخادم</span></dd></div></dl><div className="tag-section"><h3>الوسوم الحالية</h3>{tags.length ? <div className="tag-pills">{tags.map((tag) => <span key={tag.tag}>{tag.tag}{typeof tag.shortUrlsCount === 'number' && <small>{tag.shortUrlsCount}</small>}</span>)}</div> : <p>أضف الوسوم عند إنشاء رابط لتنظيم حملاتك.</p>}</div></section><section className="panel"><div className="panel-heading"><div><h2>النطاقات المُعدّة</h2><p>النطاقات المتاحة لإنشاء روابطك المختصرة.</p></div></div>{domains.length ? <div className="domain-list">{domains.map((domain) => <div key={domain.domain}><span>{domain.domain}</span>{domain.isDefault && <em>افتراضي</em>}</div>)}</div> : <div className="empty-state"><strong>لم يتم إرجاع نطاقات إضافية</strong><span>يظهر نطاق الخدمة الأساسي عند إنشاء الروابط.</span></div>}<div className="config-guide"><p>تُضبط متغيرات الربط السرية من إعدادات الخدمة، وليس من المتصفح.</p><code>SHLINK_BASE_URL=https://qrcode.abothabet.com</code><code>SHLINK_API_KEY=ضع-المفتاح-السري-هنا</code><code>ADMIN_PASSWORD=كلمة-مرور-قوية</code><code>SESSION_SECRET=قيمة-عشوائية-طويلة</code></div></section></div>;
}

function CreateModal({ busy, onClose, onSubmit }: { busy: boolean; onClose: () => void; onSubmit: (event: FormEvent<HTMLFormElement>) => void }) {
  return <div className="modal-backdrop" role="presentation"><section className="modal" role="dialog" aria-modal="true" aria-labelledby="create-title"><div className="modal-heading"><div><p className="eyebrow">رابط جديد</p><h2 id="create-title">أنشئ رابطًا مختصرًا</h2></div><button onClick={onClose} aria-label="إغلاق">×</button></div><form onSubmit={onSubmit}><label>رابط الوجهة<input name="longUrl" type="url" placeholder="https://example.com/page" required /></label><div className="form-grid"><label>عنوان داخلي<input name="title" placeholder="حملة الصيف" /></label><label>رمز مخصص<input name="customSlug" placeholder="summer" dir="ltr" /></label></div><div className="form-grid"><label>الوسوم<input name="tags" placeholder="تسويق، صيف" /></label><label>الحد الأقصى للزيارات<input name="maxVisits" type="number" min="1" placeholder="اختياري" /></label></div><div className="modal-actions"><button type="button" className="ghost-button" onClick={onClose}>إلغاء</button><button className="primary-button" disabled={busy}>{busy ? 'جارٍ الإنشاء…' : 'إنشاء الرابط'}</button></div></form></section></div>;
}

createRoot(document.getElementById('root')!).render(<App />);
