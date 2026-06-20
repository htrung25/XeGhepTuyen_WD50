import type { Page } from '@playwright/test'

/** Wrap data in the Laravel `{ success, data }` envelope apiClient unwraps. */
export const envelope = (data: unknown, message?: string) => ({
  success: true,
  data,
  ...(message ? { message } : {}),
})

type Stub = { status?: number; body: unknown }

/**
 * Intercept every `/api/**` request. `routes` maps a path suffix
 * (e.g. '/admin/auth/login') to a canned response; anything unmatched
 * returns an empty success list so dashboards render without reaching the
 * real backend — which avoids a 401 → global redirect back to login.
 */
export async function stubApi(page: Page, routes: Record<string, Stub> = {}) {
  await page.route('**/api/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    const key = Object.keys(routes).find((k) => path.endsWith(k))
    const stub: Stub = key ? routes[key] : { status: 200, body: envelope([]) }
    await route.fulfill({
      status: stub.status ?? 200,
      contentType: 'application/json',
      body: JSON.stringify(stub.body),
    })
  })
}

/** Seed a portal token before the SPA boots (simulates a logged-in session). */
export async function seedAuth(
  page: Page,
  entries: Record<string, string>,
) {
  await page.addInitScript((kv: Record<string, string>) => {
    for (const [k, v] of Object.entries(kv)) localStorage.setItem(k, v)
  }, entries)
}
