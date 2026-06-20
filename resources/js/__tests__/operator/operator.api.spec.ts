import { describe, expect, it, vi } from 'vitest'

vi.mock('@/api/client', () => ({
  apiClient: {
    send: vi.fn(() => Promise.resolve({ data: null, message: null, error: null })),
    sendForm: vi.fn(() => Promise.resolve({ data: null, message: null, error: null })),
  },
}))

import { apiClient } from '@/api/client'
import { operatorApi } from '@/api/operator.api'

describe('operatorApi → Wayfinder route contract', () => {
  it('login resolves to POST /api/operator/auth/login', () => {
    operatorApi.login({ phone: '0912345678', password: 'secret' })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/operator/auth/login', method: 'post' },
      { phone: '0912345678', password: 'secret' },
    )
  })

  it('getOnboardingFleet resolves to GET /api/operator/onboarding/fleet', () => {
    operatorApi.getOnboardingFleet()
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/operator/onboarding/fleet', method: 'get' })
  })

  it('createVehicle uploads multipart via sendForm to POST /api/operator/vehicles', () => {
    const form = new FormData()
    operatorApi.createVehicle(form)
    expect(apiClient.sendForm).toHaveBeenCalledWith({ url: '/api/operator/vehicles', method: 'post' }, form)
  })

  it('deleteRoute resolves to the newly added DELETE /api/operator/routes/{id}', () => {
    operatorApi.deleteRoute('r-1')
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/operator/routes/r-1', method: 'delete' })
  })

  it('cancelTrip resolves to POST /api/operator/trips/{id}/cancel with a reason', () => {
    operatorApi.cancelTrip('trip-1', 'Hỏng xe')
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/operator/trips/trip-1/cancel', method: 'post' },
      { reason: 'Hỏng xe' },
    )
  })

  it('bulkCreateTrips wraps the payload under a trips key', () => {
    const trips = [{ depart_at: '2026-06-21 06:00' }]
    operatorApi.bulkCreateTrips(trips)
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/operator/trips/bulk', method: 'post' }, { trips })
  })

  it('assignVehicle resolves to PUT /api/operator/drivers/{id}/vehicle', () => {
    operatorApi.assignVehicle('drv-1', 'veh-9')
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/operator/drivers/drv-1/vehicle', method: 'put' },
      { vehicle_id: 'veh-9' },
    )
  })

  it('exportManifestExcel requests a blob from the newly added export route', () => {
    operatorApi.exportManifestExcel('trip-1')
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/operator/trips/trip-1/manifest/export', method: 'post' },
      undefined,
      { blob: true },
    )
  })

  it('requestPayout resolves to POST /api/operator/revenue/payout-request', () => {
    operatorApi.requestPayout()
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/operator/revenue/payout-request', method: 'post' })
  })
})
