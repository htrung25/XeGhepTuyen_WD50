import { describe, expect, it, vi } from 'vitest'

vi.mock('@/api/client', () => ({
  apiClient: {
    send: vi.fn(() => Promise.resolve({ data: null, message: null, error: null })),
    get: vi.fn(() => Promise.resolve({ data: null, error: null })),
    put: vi.fn(() => Promise.resolve({ data: null, error: null })),
    post: vi.fn(() => Promise.resolve({ data: null, message: null, error: null })),
  },
}))

import { apiClient } from '@/api/client'
import { driverApi } from '@/api/driver.api'

describe('driverApi → Wayfinder route contract', () => {
  it('login resolves to POST /api/driver/auth/login', () => {
    driverApi.login({ phone: '0923456789', password: 'secret' })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/driver/auth/login', method: 'post' },
      { phone: '0923456789', password: 'secret' },
    )
  })

  it('getTrips bakes filter params into the query string', () => {
    driverApi.getTrips({ status: 'scheduled', date: '2026-06-20' })
    expect(apiClient.send).toHaveBeenCalledWith({
      url: '/api/driver/trips?status=scheduled&date=2026-06-20',
      method: 'get',
    })
  })

  it('getPassengers resolves to GET /api/driver/trips/{id}/passengers', () => {
    driverApi.getPassengers('trip-1')
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/driver/trips/trip-1/passengers', method: 'get' })
  })

  it('startTrip and completeTrip resolve to their POST routes', () => {
    driverApi.startTrip('trip-1')
    driverApi.completeTrip('trip-1')
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/driver/trips/trip-1/start', method: 'post' })
    expect(apiClient.send).toHaveBeenCalledWith({ url: '/api/driver/trips/trip-1/complete', method: 'post' })
  })

  it('checkin resolves to POST /api/driver/checkin with the cash flag', () => {
    driverApi.checkin({ qr_token: 'abc', cash_collected: true })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/driver/checkin', method: 'post' },
      { qr_token: 'abc', cash_collected: true },
    )
  })

  it('markAbsent resolves to the newly added POST /api/driver/checkin/absent', () => {
    driverApi.markAbsent({ trip_id: 't1', booking_id: 'b1' })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/driver/checkin/absent', method: 'post' },
      { trip_id: 't1', booking_id: 'b1' },
    )
  })

  it('updateLocation resolves to POST /api/driver/location', () => {
    driverApi.updateLocation({ trip_id: 't1', lat: 21.02, lng: 105.85 })
    expect(apiClient.send).toHaveBeenCalledWith(
      { url: '/api/driver/location', method: 'post' },
      { trip_id: 't1', lat: 21.02, lng: 105.85 },
    )
  })

  it('getNotifications stays on the legacy client (route BE not yet implemented)', () => {
    driverApi.getNotifications({ page: 1 })
    expect(apiClient.get).toHaveBeenCalledWith('/driver/notifications', { params: { page: 1 } })
  })
})
