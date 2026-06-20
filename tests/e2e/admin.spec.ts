import { expect, test } from '@playwright/test'
import { envelope, seedAuth, stubApi } from './support/api'

const ADMIN = {
  token: 'admin-tok',
  user: { id: 'a1', full_name: 'Quản trị viên', email: 'admin@xeghep.vn', role: 'admin' },
}

test.describe('Admin portal', () => {
  test('redirects to login when visiting a protected page unauthenticated', async ({ page }) => {
    await stubApi(page)
    await page.goto('/admin/dashboard')
    await expect(page).toHaveURL(/\/admin\/login/)
    await expect(page.getByRole('button', { name: 'Đăng nhập' })).toBeVisible()
  })

  test('validates that both fields are required', async ({ page }) => {
    await stubApi(page)
    await page.goto('/admin/login')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()
    await expect(page.getByText('Vui lòng nhập đầy đủ thông tin')).toBeVisible()
    await expect(page).toHaveURL(/\/admin\/login/)
  })

  test('logs in with valid credentials and lands on the dashboard', async ({ page }) => {
    await stubApi(page, { '/admin/auth/login': { body: envelope(ADMIN) } })
    await page.goto('/admin/login')

    await page.locator('input[type="email"]').fill('admin@xeghep.vn')
    await page.locator('input[type="password"]').fill('Admin@123456')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page).toHaveURL(/\/admin\/dashboard/)
    expect(await page.evaluate(() => localStorage.getItem('admin_token'))).toBe('admin-tok')
  })

  test('shows the server error and stays on login when rejected', async ({ page }) => {
    await stubApi(page, {
      '/admin/auth/login': { status: 422, body: { success: false, message: 'Email hoặc mật khẩu không đúng' } },
    })
    await page.goto('/admin/login')

    await page.locator('input[type="email"]').fill('admin@xeghep.vn')
    await page.locator('input[type="password"]').fill('wrong-pass')
    await page.getByRole('button', { name: 'Đăng nhập' }).click()

    await expect(page.getByText('Email hoặc mật khẩu không đúng')).toBeVisible()
    await expect(page).toHaveURL(/\/admin\/login/)
  })

  test('skips the login page when already authenticated', async ({ page }) => {
    await stubApi(page)
    await seedAuth(page, { admin_token: ADMIN.token, admin_user: JSON.stringify(ADMIN.user) })
    await page.goto('/admin/login')
    await expect(page).toHaveURL(/\/admin\/dashboard/)
  })
})
