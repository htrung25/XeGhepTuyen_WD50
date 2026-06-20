import { expect, test } from '@playwright/test'
import { envelope, seedAuth, stubApi } from './support/api'

const DRIVER = {
  token: 'driver-tok',
  user: {
    id: 'd1', full_name: 'Nguyễn Văn Tài', phone: '0901234567', email: null,
    avatar_url: null, rating_avg: 4.8, total_trips: 120, is_verified: true,
  },
  driver: {
    id: 'drv-1', status: 'verified', license_number: 'B2-123',
    license_expiry: '2028-12-31', operator: { company_name: 'Xe Ghép Bắc Hà' },
  },
}

test.describe('Driver portal', () => {
  test('redirects to login when visiting a protected page unauthenticated', async ({ page }) => {
    await stubApi(page)
    await page.goto('/driver/dashboard')
    await expect(page).toHaveURL(/\/driver\/login/)
    await expect(page.getByRole('heading', { name: 'Đăng nhập Tài xế' })).toBeVisible()
  })

  test('rejects an invalid Vietnamese phone number before calling the API', async ({ page }) => {
    await stubApi(page)
    await page.goto('/driver/login')

    await page.locator('input[type="tel"]').fill('0123')
    await page.locator('input[type="password"]').fill('whatever')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page.getByText(/Số điện thoại không hợp lệ/)).toBeVisible()
    await expect(page).toHaveURL(/\/driver\/login/)
  })

  test('logs in with valid credentials and lands on the dashboard', async ({ page }) => {
    await stubApi(page, { '/driver/auth/login': { body: envelope(DRIVER) } })
    await page.goto('/driver/login')

    await page.locator('input[type="tel"]').fill('0901234567')
    await page.locator('input[type="password"]').fill('secret123')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page).toHaveURL(/\/driver\/dashboard/)
    expect(await page.evaluate(() => localStorage.getItem('driver_token'))).toBe('driver-tok')
  })

  test('shows the server error and stays on login when rejected', async ({ page }) => {
    // 403 (not 401) — a pending driver is gated server-side; a 401 would trip
    // the global interceptor and hard-redirect, which is a different path.
    await stubApi(page, {
      '/driver/auth/login': { status: 403, body: { success: false, message: 'Tài khoản chưa được duyệt' } },
    })
    await page.goto('/driver/login')

    await page.locator('input[type="tel"]').fill('0901234567')
    await page.locator('input[type="password"]').fill('secret123')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page.getByText('Tài khoản chưa được duyệt')).toBeVisible()
    await expect(page).toHaveURL(/\/driver\/login/)
  })

  test('skips the login page when already authenticated', async ({ page }) => {
    await stubApi(page)
    await seedAuth(page, {
      driver_token: DRIVER.token,
      driver_user: JSON.stringify(DRIVER.user),
      driver_info: JSON.stringify(DRIVER.driver),
    })
    await page.goto('/driver/login')
    await expect(page).toHaveURL(/\/driver\/dashboard/)
  })
})
