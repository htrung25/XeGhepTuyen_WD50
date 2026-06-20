import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@/api/operator.api', () => ({
  operatorApi: {
    getTrips: vi.fn(),
    getRoutes: vi.fn(),
    getVehicles: vi.fn(),
    getDrivers: vi.fn(),
  },
}))

import { operatorApi } from '@/api/operator.api'
import { useOperatorStore } from '@/stores/operator.store'

function makeTrip(id: string, departAt: string) {
  return {
    id,
    trip_code: `T${id}`,
    route_name: 'Hà Nội → Hải Phòng',
    depart_at: departAt,
    status: 'scheduled',
    booking_count: 2,
    total_seats: 7,
    price: 150_000,
  }
}

describe('useOperatorStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('fetchTrips loads the list and toggles isLoading around the request', async () => {
    const trips = [makeTrip('1', '2026-06-20 06:00:00')]
    vi.mocked(operatorApi.getTrips).mockResolvedValue({ data: trips, message: null, error: null })
    const store = useOperatorStore()

    const promise = store.fetchTrips()
    expect(store.isLoading).toBe(true)
    await promise

    expect(store.trips).toEqual(trips)
    expect(store.isLoading).toBe(false)
  })

  it('todayTrips only keeps trips departing today', () => {
    const store = useOperatorStore()
    const todayIso = new Date().toISOString()
    store.trips = [makeTrip('today', todayIso), makeTrip('old', '2000-01-01 06:00:00')]

    expect(store.todayTrips).toHaveLength(1)
    expect(store.todayTrips[0].id).toBe('today')
  })

  it('activeRoutes filters out inactive routes', () => {
    const store = useOperatorStore()
    store.routes = [
      { id: 'r1', route_code: 'A', name: 'A', origin: 'HN', destination: 'HP', distance_km: 120, is_active: true },
      { id: 'r2', route_code: 'B', name: 'B', origin: 'HN', destination: 'HP', distance_km: 120, is_active: false },
    ]

    expect(store.activeRoutes.map(r => r.id)).toEqual(['r1'])
  })

  it('addTrip prepends and removeTrip deletes by id', () => {
    const store = useOperatorStore()
    store.trips = [makeTrip('1', '2026-06-20 06:00:00')]

    store.addTrip(makeTrip('2', '2026-06-20 08:00:00'))
    expect(store.trips[0].id).toBe('2')

    store.removeTrip('1')
    expect(store.trips.map(t => t.id)).toEqual(['2'])
  })
})
