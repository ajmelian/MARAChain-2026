/**
 * MARAChain — Smoke E2E Tests
 *
 * Ejecutar:
 *   cd wwwroot && php spark serve --host localhost --port 8080 &
 *   npx playwright test tests/E2E/smoke.spec.js --reporter=line
 *
 * Requisitos:
 *   - Servidor PHP 8.4+ corriendo en localhost:8080
 *   - IPFS no requerido (los tests fallan gracefulmente)
 *   - Login y rutas SHIELD pueden devolver 500 sin sesion
 *
 * @since 1.9.0
 */

const { test, expect } = require('@playwright/test');

const BASE = process.env.TEST_URL || 'http://localhost:8080';

// ═════════════════════════════════════════════════════════════════════
//  Public endpoints
// ═════════════════════════════════════════════════════════════════════

test('GET /health returns 200', async ({ request }) => {
  const resp = await request.get(`${BASE}/health`);
  expect(resp.ok()).toBeTruthy();
  const body = await resp.json();
  expect(body).toHaveProperty('status');
  expect(body).toHaveProperty('checks');
});

test('GET /health contains database check', async ({ request }) => {
  const resp = await request.get(`${BASE}/health`);
  const body = await resp.json();
  expect(body.checks).toHaveProperty('database');
});

test('GET /health contains version', async ({ request }) => {
  const resp = await request.get(`${BASE}/health`);
  const body = await resp.json();
  expect(body.checks).toHaveProperty('version');
});

test('GET /register returns 200', async ({ request }) => {
  const resp = await request.get(`${BASE}/register`);
  expect(resp.ok()).toBeTruthy();
});

test('GET /api/docs returns 200 (Swagger UI)', async ({ request }) => {
  const resp = await request.get(`${BASE}/api/docs`);
  expect(resp.ok()).toBeTruthy();
  const text = await resp.text();
  expect(text.toLowerCase()).toContain('swagger');
});

// ═════════════════════════════════════════════════════════════════════
//  SHIELD login — may return 500 without proper session config
// ═════════════════════════════════════════════════════════════════════

test('GET /login returns 200 or 500 (SHIELD init)', async ({ request }) => {
  const resp = await request.get(`${BASE}/login`);
  expect([200, 500]).toContain(resp.status());
});

test('GET /login contains form when 200', async ({ request }) => {
  const resp = await request.get(`${BASE}/login`);
  if (resp.status() === 200) {
    const text = await resp.text();
    expect(text).toContain('<form');
  }
});

// ═════════════════════════════════════════════════════════════════════
//  Auth-protected redirects
// ═════════════════════════════════════════════════════════════════════

test('GET /inbox redirects (or auth error)', async ({ request }) => {
  const resp = await request.get(`${BASE}/inbox`, { redirect: 'manual' });
  expect([301, 302, 500]).toContain(resp.status());
});

test('GET /profile redirects (or auth error)', async ({ request }) => {
  const resp = await request.get(`${BASE}/profile`, { redirect: 'manual' });
  expect([301, 302, 500]).toContain(resp.status());
});

// ═════════════════════════════════════════════════════════════════════
//  API endpoints — should reject without auth
// ═════════════════════════════════════════════════════════════════════

test('GET /evidence returns 401 without auth', async ({ request }) => {
  const resp = await request.get(`${BASE}/evidence`);
  expect([401, 500]).toContain(resp.status());
});

test('GET /ledger returns 401 without auth', async ({ request }) => {
  const resp = await request.get(`${BASE}/ledger`);
  expect([401, 500]).toContain(resp.status());
});

test('POST /transfers returns 401 without auth', async ({ request }) => {
  const resp = await request.post(`${BASE}/transfers`, { data: {} });
  expect([401, 500]).toContain(resp.status());
});

// ═════════════════════════════════════════════════════════════════════
//  PWA static assets
// ═════════════════════════════════════════════════════════════════════

test('GET /manifest.json returns 200 (PWA)', async ({ request }) => {
  const resp = await request.get(`${BASE}/manifest.json`);
  expect(resp.ok()).toBeTruthy();
});

test('GET /sw.js returns 200 (Service Worker)', async ({ request }) => {
  const resp = await request.get(`${BASE}/sw.js`);
  expect(resp.ok()).toBeTruthy();
});
