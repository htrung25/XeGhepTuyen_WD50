import { expect, test } from '@playwright/test'
import { envelope, seedAuth, stubApi } from './support/api'

const OPERATOR = {
  token: 'operator-tok',
  user: { id: 'u1', full_name: 'Chủ nhà xe', email: 'op@xeghep.vn', phone: '0912345678' },
  operator: { id: 'op-1', company_name: 'Xe Ghép Bắc Hà', status: 'verified', commission_rate: 10 },
}

test.describe('Operator portal', () => {
  test('redirects to login when visiting a protected page unauthenticated', async ({ page }) => {
    await stubApi(page)
    await page.goto('/operator/dashboard')
    await expect(page).toHaveURL(/\/operator\/login/)
    await expect(page.getByRole('button', { name: 'Đăng nhập' })).toBeVisible()
  })

  test('validates that both fields are required', async ({ page }) => {
    await stubApi(page)
    await page.goto('/operator/login')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()
    await expect(page.getByText(/Vui lòng nhập đầy đủ/)).toBeVisible()
    await expect(page).toHaveURL(/\/operator\/login/)
  })

  test('logs in with valid credentials and lands on the dashboard', async ({ page }) => {
    await stubApi(page, { '/operator/auth/login': { body: envelope(OPERATOR) } })
    await page.goto('/operator/login')

    await page.locator('input[type="tel"]').fill('0912345678')
    await page.locator('input[type="password"]').fill('secret123')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page).toHaveURL(/\/operator\/dashboard/)
    expect(await page.evaluate(() => localStorage.getItem('operator_token'))).toBe('operator-tok')
  })

  test('shows the server error and stays on login when rejected', async ({ page }) => {
    await stubApi(page, {
      '/operator/auth/login': { status: 422, body: { success: false, message: 'Sai số điện thoại hoặc mật khẩu' } },
    })
    await page.goto('/operator/login')

    await page.locator('input[type="tel"]').fill('0912345678')
    await page.locator('input[type="password"]').fill('wrong-pass')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page.getByText('Sai số điện thoại hoặc mật khẩu')).toBeVisible()
    await expect(page).toHaveURL(/\/operator\/login/)
  })

  test('skips the login page when already authenticated', async ({ page }) => {
    await stubApi(page)
    await seedAuth(page, {
      operator_token: OPERATOR.token,
      operator_user: JSON.stringify(OPERATOR.user),
      operator_info: JSON.stringify(OPERATOR.operator),
    })
    await page.goto('/operator/login')
    await expect(page).toHaveURL(/\/operator\/dashboard/)
  })
})
