import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface SearchParams {
  from_city: string
  to_city: string
  date: string
  passengers: number
  trip_type: 'one_way' | 'round_trip'
}

export interface TripResult {
  id: string
  tracking_code: string
  depart_at: string
  arrive_at: string
  price: number
  available_seats: number
  route: { origin_city: string; dest_city: string; distance_km: number; est_duration_min: number }
  vehicle: { vehicle_type: string; seat_count: number; amenities: string[] }
  driver: { full_name: string; rating_avg: number }
  operator: { company_name: string }
  pickup_stops: RouteStop[]
  dropoff_stops: RouteStop[]
}

export interface RouteStop {
  id: string
  stop_name: string
  address: string
  stop_order: number
  is_pickup: boolean
  is_dropoff: boolean
}

export interface SeatInfo {
  id: string
  seat_code: string
  seat_type: string
  price: number
  status: 'available' | 'booked' | 'locked' | 'driver'
}

export interface BookingDraft {
  trip_id: string
  seat_codes: string[]
  seats: SeatInfo[]
  pickup_stop_id: string
  dropoff_stop_id: string
  pickup_detail: string
  note: string
  voucher_code: string
  voucher_discount: number
  passenger_name: string
  passenger_phone: string
}

export interface Booking {
  id: string
  tracking_code: string
  status: 'pending_payment' | 'confirmed' | 'cancelled' | 'completed'
  trip: TripResult
  seats: SeatInfo[]
  pickup_stop: RouteStop
  dropoff_stop: RouteStop
  passenger_name: string
  passenger_phone: string
  total_amount: number
  paid_at: string | null
  created_at: string
}

export interface TrackingData {
  trip_id: string
  status: string
  vehicle_location: { lat: number; lng: number } | null
  current_stop: { stop_name: string; address: string } | null
  eta_minutes: number | null
  next_stop: { stop_name: string } | null
  updated_at: string
}

export interface WalletTransaction {
  id: string
  type: 'topup' | 'payment' | 'refund'
  amount: number
  description: string
  created_at: string
}

export interface Wallet {
  balance: number
  pending_amount: number
  currency: string
  transactions: WalletTransaction[]
}

export const useCustomerStore = defineStore('customer', () => {
  // ─── Search ────────────────────────────────────────────────────────────
  const searchParams = ref<SearchParams>({
    from_city: '',
    to_city: '',
    date: new Date().toISOString().split('T')[0],
    passengers: 1,
    trip_type: 'one_way',
  })
  const searchResults = ref<TripResult[]>([])
  const selectedTrip  = ref<TripResult | null>(null)

  // ─── Booking draft (in-progress checkout) ─────────────────────────────
  const bookingDraft = ref<BookingDraft>({
    trip_id: '',
    seat_codes: [],
    seats: [],
    pickup_stop_id: '',
    dropoff_stop_id: '',
    pickup_detail: '',
    note: '',
    voucher_code: '',
    voucher_discount: 0,
    passenger_name: '',
    passenger_phone: '',
  })

  // ─── Bookings list ─────────────────────────────────────────────────────
  const bookings      = ref<Booking[]>([])
  const currentBooking = ref<Booking | null>(null)
  /** Legacy scalar kept for backward compat — prefer currentBooking.id */
  const currentBookingId = ref<string | null>(null)

  // ─── Live tracking ─────────────────────────────────────────────────────
  const trackingData = ref<TrackingData | null>(null)

  // ─── Wallet ────────────────────────────────────────────────────────────
  const wallet        = ref<Wallet | null>(null)
  const walletBalance = ref(0)

  // ─── Actions ───────────────────────────────────────────────────────────
  function resetBooking() {
    bookingDraft.value = {
      trip_id: '',
      seat_codes: [],
      seats: [],
      pickup_stop_id: '',
      dropoff_stop_id: '',
      pickup_detail: '',
      note: '',
      voucher_code: '',
      voucher_discount: 0,
      passenger_name: '',
      passenger_phone: '',
    }
    currentBookingId.value = null
    currentBooking.value   = null
  }

  function setCurrentBooking(b: Booking) {
    currentBooking.value   = b
    currentBookingId.value = b.id
  }

  function updateTracking(data: TrackingData) {
    trackingData.value = data
  }

  function setWallet(w: Wallet) {
    wallet.value        = w
    walletBalance.value = w.balance
  }

  return {
    // state
    searchParams,
    searchResults,
    selectedTrip,
    bookingDraft,
    bookings,
    currentBooking,
    currentBookingId,
    trackingData,
    wallet,
    walletBalance,
    // actions
    resetBooking,
    setCurrentBooking,
    updateTracking,
    setWallet,
  }
})
