import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useDriverStore, type DriverTrip } from '@/stores/driver.store'

function trip(id: string, status: DriverTrip['status']): DriverTrip {
  return {
    id,
    tracking_code: `TRK${id}`,
    depart_at: '2026-06-20 06:00:00',
    arrive_at: '2026-06-20 08:00:00',
    status,
    price: 150_000,
    available_seats: 3,
    route: { origin_city: 'Hà Nội', dest_city: 'Hải Phòng', est_duration_min: 120 },
    vehicle: { plate_number: '30A-12345', vehicle_type: 'mpv_7', seat_count: 7 },
    passengers_count: 4,
    checked_in_count: 0,
  }
}

describe('useDriverStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('activeOrNextTrip', () => {
    it('prefers the in_progress trip over scheduled ones', () => {
      const store = useDriverStore()
      store.todayTrips = [trip('a', 'scheduled'), trip('b', 'in_progress')]
      expect(store.activeOrNextTrip?.id).toBe('b')
    })

    it('falls back to the first scheduled trip when none are in progress', () => {
      const store = useDriverStore()
      store.todayTrips = [trip('a', 'completed'), trip('b', 'scheduled'), trip('c', 'scheduled')]
      expect(store.activeOrNextTrip?.id).toBe('b')
    })

    it('returns null when there is nothing active or upcoming', () => {
      const store = useDriverStore()
      store.todayTrips = [trip('a', 'completed'), trip('b', 'cancelled')]
      expect(store.activeOrNextTrip).toBeNull()
    })
  })

  it('setEarnings fills structured earnings and mirrors today/week scalars', () => {
    const store = useDriverStore()

    store.setEarnings({
      today: 300_000,
      week: 1_500_000,
      month: 6_000_000,
      total_trips: 40,
      total_passengers: 60,
      total_km: 4800,
      daily_breakdown: [],
    })

    expect(store.earnings?.month).toBe(6_000_000)
    expect(store.todayEarnings).toBe(300_000)
    expect(store.weekEarnings).toBe(1_500_000)
  })

  it('updateLocation stamps the location with an updated_at timestamp', () => {
    const store = useDriverStore()

    store.updateLocation({ lat: 21.02, lng: 105.85, speed: 40, heading: 90, accuracy: 5 })

    expect(store.currentLocation?.lat).toBe(21.02)
    expect(store.currentLocation?.lng).toBe(105.85)
    expect(typeof store.currentLocation?.updated_at).toBe('string')
  })

  it('setOnlineStatus toggles the in-memory online flag', () => {
    const store = useDriverStore()
    store.setOnlineStatus(true)
    expect(store.isOnline).toBe(true)
  })
})
