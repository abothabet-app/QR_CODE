import crypto from 'node:crypto';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import express, { NextFunction, Request, Response } from 'express';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const app = express();
const port = Number(process.env.PORT ?? 3000);
const dashboardPassword = process.env.ADMIN_PASSWORD ?? '';
const sessionSecret = process.env.SESSION_SECRET ?? '';
const shlinkBaseUrl = (process.env.SHLINK_BASE_URL ?? 'https://qrcode.abothabet.com').replace(/\/$/, '');
const shlinkApiKey = process.env.SHLINK_API_KEY ?? '';

class ApiError extends Error {
  constructor(public readonly status: number, message: string) {
    super(message);
  }
}

function hasRequiredConfiguration(): boolean {
  return Boolean(dashboardPassword && sessionSecret && shlinkBaseUrl && shlinkApiKey);
}

function parseCookies(request: Request): Record<string, string> {
  const cookieHeader = request.headers.cookie ?? '';
  return cookieHeader.split(';').reduce<Record<string, string>>((cookies, item) => {
    const [name, ...parts] = item.trim().split('=');
    if (name && parts.length > 0) cookies[name] = decodeURIComponent(parts.join('='));
    return cookies;
  }, {});
}

function signSession(expiresAt: number): string {
  const payload = Buffer.from(JSON.stringify({ exp: expiresAt })).toString('base64url');
  const signature = crypto.createHmac('sha256', sessionSecret).update(payload).digest('base64url');
  return `${payload}.${signature}`;
}

function sessionIsValid(request: Request): boolean {
  if (!sessionSecret) return false;
  const token = parseCookies(request).abunabit_session;
  if (!token) return false;

  const [payload, signature] = token.split('.');
  if (!payload || !signature) return false;
  const expected = crypto.createHmac('sha256', sessionSecret).update(payload).digest('base64url');
  if (signature.length !== expected.length || !crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) {
    return false;
  }

  try {
    const session = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8')) as { exp?: number };
    return typeof session.exp === 'number' && session.exp > Date.now();
  } catch {
    return false;
  }
}

function requireSession(request: Request, response: Response, next: NextFunction): void {
  if (!sessionIsValid(request)) {
    response.status(401).json({ message: 'انتهت جلسة الدخول أو لا تملك صلاحية الوصول.' });
    return;
  }
  next();
}

async function shlinkRequest(apiPath: string, init: RequestInit = {}): Promise<unknown> {
  if (!hasRequiredConfiguration()) {
    throw new ApiError(503, 'إعدادات الخادم غير مكتملة. أضف متغيرات البيئة المطلوبة في خدمة لوحة الإدارة.');
  }

  const response = await fetch(`${shlinkBaseUrl}/rest/v3${apiPath}`, {
    ...init,
    headers: {
      Accept: 'application/json',
      'X-Api-Key': shlinkApiKey,
      ...(init.body ? { 'Content-Type': 'application/json' } : {}),
      ...init.headers,
    },
  });

  if (response.status === 204) return null;
  const contentType = response.headers.get('content-type') ?? '';
  const data: unknown = contentType.includes('application/json')
    ? await response.json()
    : await response.text();

  if (!response.ok) {
    const details = typeof data === 'object' && data !== null && 'detail' in data
      ? String((data as { detail: unknown }).detail)
      : 'تعذر تنفيذ الطلب لدى خدمة الروابط.';
    throw new ApiError(response.status, details);
  }

  return data;
}

function queryString(allowed: string[], query: Request['query']): string {
  const params = new URLSearchParams();
  for (const key of allowed) {
    const value = query[key];
    if (typeof value === 'string' && value.trim()) params.set(key, value.trim());
  }
  const serialized = params.toString();
  return serialized ? `?${serialized}` : '';
}

app.disable('x-powered-by');
app.use(express.json({ limit: '512kb' }));

app.get('/api/session', (request, response) => {
  response.json({
    authenticated: sessionIsValid(request),
    configured: hasRequiredConfiguration(),
    serviceUrl: shlinkBaseUrl,
  });
});

app.post('/api/session', (request, response) => {
  if (!dashboardPassword || !sessionSecret) {
    response.status(503).json({ message: 'يجب ضبط ADMIN_PASSWORD وSESSION_SECRET في إعدادات الخدمة أولًا.' });
    return;
  }

  const password = typeof request.body?.password === 'string' ? request.body.password : '';
  const expected = Buffer.from(dashboardPassword);
  const provided = Buffer.from(password);
  const matches = expected.length === provided.length && crypto.timingSafeEqual(expected, provided);
  if (!matches) {
    response.status(401).json({ message: 'كلمة المرور غير صحيحة.' });
    return;
  }

  const maxAge = 1000 * 60 * 60 * 12;
  response.setHeader('Set-Cookie', [
    `abunabit_session=${encodeURIComponent(signSession(Date.now() + maxAge))}`,
    'Path=/',
    'HttpOnly',
    'Secure',
    'SameSite=Strict',
    `Max-Age=${Math.floor(maxAge / 1000)}`,
  ].join('; '));
  response.status(204).end();
});

app.delete('/api/session', (_request, response) => {
  response.setHeader('Set-Cookie', 'abunabit_session=; Path=/; HttpOnly; Secure; SameSite=Strict; Max-Age=0');
  response.status(204).end();
});

app.get('/api/dashboard-summary', requireSession, async (_request, response, next) => {
  try {
    const [shortUrls, visits] = await Promise.all([
      shlinkRequest('/short-urls?itemsPerPage=8&orderBy=dateCreated&orderDir=DESC'),
      shlinkRequest('/visits'),
    ]);
    response.json({ shortUrls, visits });
  } catch (error) {
    next(error);
  }
});

app.get('/api/short-urls', requireSession, async (request, response, next) => {
  try {
    const query = queryString(['page', 'itemsPerPage', 'searchTerm', 'tags', 'orderBy', 'orderDir'], request.query);
    response.json(await shlinkRequest(`/short-urls${query}`));
  } catch (error) {
    next(error);
  }
});

app.post('/api/short-urls', requireSession, async (request, response, next) => {
  try {
    const longUrl = typeof request.body?.longUrl === 'string' ? request.body.longUrl.trim() : '';
    if (!/^https?:\/\//i.test(longUrl)) {
      throw new ApiError(400, 'أدخل رابط وجهة صحيحًا يبدأ بـ http:// أو https://.');
    }

    const payload = {
      longUrl,
      ...(typeof request.body?.customSlug === 'string' && request.body.customSlug.trim()
        ? { customSlug: request.body.customSlug.trim() }
        : {}),
      ...(typeof request.body?.title === 'string' && request.body.title.trim()
        ? { title: request.body.title.trim() }
        : {}),
      ...(Array.isArray(request.body?.tags)
        ? { tags: request.body.tags.filter((tag: unknown) => typeof tag === 'string' && tag.trim()) }
        : {}),
      ...(typeof request.body?.maxVisits === 'number' && request.body.maxVisits > 0
        ? { maxVisits: request.body.maxVisits }
        : {}),
    };

    response.status(201).json(await shlinkRequest('/short-urls', {
      method: 'POST',
      body: JSON.stringify(payload),
    }));
  } catch (error) {
    next(error);
  }
});

app.patch('/api/short-urls/:shortCode', requireSession, async (request, response, next) => {
  try {
    const rawShortCode = request.params.shortCode;
    if (typeof rawShortCode !== 'string') throw new ApiError(400, 'رمز الرابط غير صالح.');
    const shortCode = encodeURIComponent(rawShortCode);
    response.json(await shlinkRequest(`/short-urls/${shortCode}`, {
      method: 'PATCH',
      body: JSON.stringify(request.body ?? {}),
    }));
  } catch (error) {
    next(error);
  }
});

app.delete('/api/short-urls/:shortCode', requireSession, async (request, response, next) => {
  try {
    const rawShortCode = request.params.shortCode;
    if (typeof rawShortCode !== 'string') throw new ApiError(400, 'رمز الرابط غير صالح.');
    const shortCode = encodeURIComponent(rawShortCode);
    await shlinkRequest(`/short-urls/${shortCode}`, { method: 'DELETE' });
    response.status(204).end();
  } catch (error) {
    next(error);
  }
});

app.get('/api/tags', requireSession, async (_request, response, next) => {
  try {
    response.json(await shlinkRequest('/tags'));
  } catch (error) {
    next(error);
  }
});

app.get('/api/domains', requireSession, async (_request, response, next) => {
  try {
    response.json(await shlinkRequest('/domains'));
  } catch (error) {
    next(error);
  }
});

app.use(express.static(path.join(__dirname, 'dist'), { index: false, maxAge: '1h' }));
app.get('*', (_request, response) => response.sendFile(path.join(__dirname, 'dist', 'index.html')));

app.use((error: unknown, _request: Request, response: Response, _next: NextFunction) => {
  const status = error instanceof ApiError ? error.status : 500;
  const message = error instanceof Error ? error.message : 'حدث خطأ غير متوقع.';
  response.status(status).json({ message });
});

app.listen(port, '0.0.0.0', () => {
  console.log(`لوحة أبونابت تعمل على المنفذ ${port}`);
});
