import { beforeEach, describe, expect, it, vi } from 'vitest'

// Capture the interceptor handlers the client registers at import time so we
// can drive them directly (token attach + global 401 handling).
type ReqFn = (config: { headers: Record<string, string> }) => { headers: Record<string, string> }
type ResErrFn = (err: unknown) => Promise<never>

// vi.hoisted runs before vi.mock is hoisted, so the shared stub is initialized
// by the time the axios factory references it.
const { http, requestHandlers, responseErrorHandlers } = vi.hoisted(() => {
  const requestHandlers: ReqFn[] = []
  const responseErrorHandlers: ResErrFn[] = []
  const http = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    postForm: vi.fn(),
    interceptors: {
      request: { use: (fn: ReqFn) => requestHandlers.push(fn) },
      response: { use: (_ok: unknown, err: ResErrFn) => responseErrorHandlers.push(err) },
    },
  }
  return { http, requestHandlers, responseErrorHandlers }
})

vi.mock('axios', () => ({ default: { create: () => http } }))

// Imported after the mock is registered.
import { apiClient } from '@/api/client'

function stubLocation(pathname: string) {
  Object.defineProperty(window, 'location', {
    value: { pathname, href: '' },
    writable: true,
    configurable: true,
  })
}

describe('apiClient envelope handling', () => {
  beforeEach(() => {
    stubLocation('/admin')
  })

  it('unwraps the Laravel { success, data } envelope exactly one level on GET', async () => {
    http.get.mockResolvedValueOnce({ data: { success: true, data: { total_revenue: 999 } } })

    const res = await apiClient.get('/admin/finance/summary')

    expect(res).toEqual({ data: { total_revenue: 999 }, error: null })
  })

  it('returns the server message on POST success alongside data', async () => {
    http.post.mockResolvedValueOnce({ data: { success: true, data: { id: 'x' }, message: 'Đã duyệt' } })

    const res = await apiClient.post('/admin/operators/x/approve', { commission_rate: 10 })

    expect(res).toEqual({ data: { id: 'x' }, message: 'Đã duyệt', error: null })
  })

  it('surfaces the server message as error on failure', async () => {
    http.get.mockRejectedValueOnce({ response: { data: { message: 'Không có quyền' } } })

    const res = await apiClient.get('/admin/users')

    expect(res.data).toBeNull()
    expect(res.error).toBe('Không có quyền')
  })

  it('falls back to a Vietnamese default error when the gateway gives no message', async () => {
    http.post.mockRejectedValueOnce({})

    const res = await apiClient.post('/driver/checkin', { qr_token: 'bad' })

    expect(res.error).toBe('Có lỗi xảy ra')
  })
})

describe('apiClient interceptors', () => {
  it('attaches the token of the active portal derived from the URL', () => {
    stubLocation('/operator/trips')
    localStorage.setItem('operator_token', 'op-tok')

    const config = requestHandlers[0]({ headers: {} })

    expect(config.headers.Authorization).toBe('Bearer op-tok')
  })

  it('does not attach a foreign portal token', () => {
    stubLocation('/operator/trips')
    localStorage.setItem('admin_token', 'admin-tok') // wrong portal

    const config = requestHandlers[0]({ headers: {} })

    expect(config.headers.Authorization).toBeUndefined()
  })

  it('clears the token and redirects to the portal login on an expired-session 401', async () => {
    stubLocation('/driver/dashboard')
    localStorage.setItem('driver_token', 'd-tok')

    await expect(
      responseErrorHandlers[0]({ response: { status: 401 }, config: { url: '/api/driver/trips' } }),
    ).rejects.toBeDefined()

    expect(localStorage.getItem('driver_token')).toBeNull()
    expect(window.location.href).toBe('/driver/login')
  })

  it('does NOT redirect on a 401 from a login attempt (bad credentials)', async () => {
    stubLocation('/admin/login') // no token on the login page

    await expect(
      responseErrorHandlers[0]({ response: { status: 401 }, config: { url: '/api/admin/auth/login' } }),
    ).rejects.toBeDefined()

    expect(window.location.href).toBe('') // page must NOT reload
  })

  it('does NOT redirect on a 401 when there is no active session token', async () => {
    stubLocation('/driver/dashboard') // token absent → not an expired session

    await expect(
      responseErrorHandlers[0]({ response: { status: 401 }, config: { url: '/api/driver/trips' } }),
    ).rejects.toBeDefined()

    expect(window.location.href).toBe('')
  })
})
