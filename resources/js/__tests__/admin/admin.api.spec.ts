import { describe, expect, it, vi } from 'vitest'

// Mock the HTTP layer; the Wayfinder action imports stay real, so each test
// verifies the generated route resolves to the expected URL + method.
vi.mock('@/api/client', () => ({
  apiClient: {
    send: vi.fn(() => Promise.resolve({ data: null, message: null, error: null })),
    sendForm: vi.fn(() => Promise.resolve({ data: null, message: null, error: null })),
    get: vi.fn(() => Promise.resolve({ data: null, error: null })),
    put: vi.fn(() => Promise.resolve({ data: null, error: null })),
  },
}))

import { adminApi } from '@/api/admin.api'
import { apiClient } from '@/api/client'

describe('adminApi → Wayfinder route contract', () => {
  it('login resolves to POST /api/admin/auth/login with credentials', () => {
    adminApi.login({ email: 'admin@xeghep.vn', password: 'secret' })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/admin/auth/login', method: 'post' },
      { email: 'admin@xeghep.vn', password: 'secret' },
    )
  })

  it('getDashboard resolves to GET /api/admin/dashboard', () => {
    adminApi.getDashboard()
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/admin/dashboard', method: 'get' })
  })

  it('getOperators bakes filter params into the query string', () => {
    adminApi.getOperators({ status: 'pending' })
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/admin/operators?status=pending', method: 'get' })
  })

  it('verifyOperator resolves to POST /api/admin/operators/{id}/approve', () => {
    adminApi.verifyOperator('op-1', { commission_rate: 12 })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/admin/operators/op-1/approve', method: 'post' },
      { commission_rate: 12 },
    )
  })

  it('verifyDriver resolves to POST /api/admin/drivers/{id}/approve', () => {
    adminApi.verifyDriver('drv-9')
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/admin/drivers/drv-9/approve', method: 'post' })
  })

  it('runAutoResolveTrips resolves to POST /api/admin/trips/auto-resolve', () => {
    adminApi.runAutoResolveTrips()
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/admin/trips/auto-resolve', method: 'post' })
  })

  it('getFinanceOverview hits /api/admin/finance/summary (not /overview)', () => {
    adminApi.getFinanceOverview({ period: 'month' })
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/admin/finance/summary?period=month', method: 'get' })
  })

  it('updateVoucher resolves to the newly added PUT /api/admin/vouchers/{id}', () => {
    adminApi.updateVoucher('v-3', { is_active: false })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/admin/vouchers/v-3', method: 'put' },
      { is_active: false },
    )
  })

  it('deleteVoucher resolves to DELETE /api/admin/vouchers/{id}', () => {
    adminApi.deleteVoucher('v-3')
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/admin/vouchers/v-3', method: 'delete' })
  })

  it('getDashboardMap stays on the legacy client (route BE not yet implemented)', () => {
    adminApi.getDashboardMap()
    expect(apiClient.get).toHaveBeenCalledWith('/admin/dashboard/map')
  })
})
