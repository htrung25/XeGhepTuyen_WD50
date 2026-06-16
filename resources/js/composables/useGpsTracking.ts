import { ref, onUnmounted } from 'vue'
import { driverApi } from '@/api/driver.api'

interface GpsState {
  lat: number | null
  lng: number | null
  speed: number | null
  heading: number | null
  accuracy: number | null
  error: string | null
}

export function useGpsTracking(tripId: string) {
  const state       = ref<GpsState>({ lat: null, lng: null, speed: null, heading: null, accuracy: null, error: null })
  const isTracking  = ref(false)
  let watchId: number | null    = null
  let sendInterval: ReturnType<typeof setInterval> | null = null

  function onPosition(pos: GeolocationPosition) {
    state.value = {
      lat:      pos.coords.latitude,
      lng:      pos.coords.longitude,
      speed:    pos.coords.speed,
      heading:  pos.coords.heading,
      accuracy: pos.coords.accuracy,
      error:    null,
    }
  }

  function onError(err: GeolocationPositionError) {
    state.value.error = err.message
  }

  async function sendLocation() {
    if (state.value.lat == null || state.value.lng == null) return
    await driverApi.updateLocation({
      trip_id: tripId,
      lat:     state.value.lat,
      lng:     state.value.lng,
      speed:   state.value.speed ?? undefined,
      heading: state.value.heading ?? undefined,
    })
  }

  function startTracking() {
    if (!navigator.geolocation) {
      state.value.error = 'Thiết bị không hỗ trợ GPS'
      return
    }
    isTracking.value = true

    // Continuous position watch
    watchId = navigator.geolocation.watchPosition(onPosition, onError, {
      enableHighAccuracy: true,
      maximumAge:         5000,
      timeout:            10000,
    })

    // Send to server every 15 seconds
    sendInterval = setInterval(sendLocation, 15000)
  }

  function stopTracking() {
    isTracking.value = false
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId)
      watchId = null
    }
    if (sendInterval !== null) {
      clearInterval(sendInterval)
      sendInterval = null
    }
  }

  // Auto-stop when component unmounts
  onUnmounted(stopTracking)

  return { state, isTracking, startTracking, stopTracking }
}
