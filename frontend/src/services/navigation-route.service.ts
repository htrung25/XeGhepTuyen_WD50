export interface NavigationCoordinate {
    lat: number;
    lng: number;
}

export interface NavigationWaypoint extends NavigationCoordinate {
    label: string;
    order: number;
}

export interface NavigationRouteResult {
    coordinates: [number, number][];
    distanceMeters: number;
    durationSeconds: number;
    nextStopDistanceMeters: number;
    nextStopDurationSeconds: number;
}

interface PassengerStop {
    stop_name?: string;
    address?: string;
    lat?: number | string | null;
    lng?: number | string | null;
    stop_order?: number | string | null;
}

interface NavigationPassenger {
    checked_in?: boolean;
    booking_status?: string;
    pickup_stop?: PassengerStop | null;
    dropoff_stop?: PassengerStop | null;
}

const MAX_MAPBOX_COORDINATES = 25;

function normalizedStop(
    stop: PassengerStop | null | undefined,
    fallbackOrder: number,
): NavigationWaypoint | null {
    const lat = Number(stop?.lat);
    const lng = Number(stop?.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;

    const rawOrder = Number(stop?.stop_order);

    return {
        lat,
        lng,
        label: stop?.stop_name || stop?.address || 'Điểm dừng',
        order: Number.isFinite(rawOrder) ? rawOrder : fallbackOrder,
    };
}

function uniqueOrderedStops(stops: NavigationWaypoint[]) {
    const seen = new Set<string>();

    return stops
        .sort((left, right) => left.order - right.order)
        .filter((stop) => {
            const key = `${stop.lat.toFixed(6)},${stop.lng.toFixed(6)}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });
}

/**
 * Giữ quy tắc nghiệp vụ: luôn đón hết khách trước, sau đó mới đi qua các điểm trả.
 * Mapbox Directions chỉ tối ưu đường đi qua danh sách đã cho, không được phép đảo
 * một điểm trả lên trước điểm đón.
 */
export function buildNavigationWaypoints(
    currentPosition: NavigationCoordinate,
    passengers: NavigationPassenger[],
): NavigationWaypoint[] {
    const activePassengers = passengers.filter(
        (passenger) => passenger.booking_status !== 'no_show',
    );

    const pickupStops = activePassengers
        .filter((passenger) => !passenger.checked_in)
        .map((passenger, index) =>
            normalizedStop(passenger.pickup_stop, 1_000 + index),
        )
        .filter((stop): stop is NavigationWaypoint => stop !== null);

    const dropoffStops = activePassengers
        .map((passenger, index) =>
            normalizedStop(passenger.dropoff_stop, 2_000 + index),
        )
        .filter((stop): stop is NavigationWaypoint => stop !== null);

    const waypoints = [
        {
            ...currentPosition,
            label: 'Vị trí của bạn',
            order: 0,
        },
        ...uniqueOrderedStops(pickupStops),
        ...uniqueOrderedStops(dropoffStops),
    ];

    if (waypoints.length > MAX_MAPBOX_COORDINATES) {
        throw new Error(
            `Tuyến có ${waypoints.length} điểm, vượt giới hạn ${MAX_MAPBOX_COORDINATES} điểm của Mapbox Directions`,
        );
    }

    return waypoints;
}

export async function fetchNavigationRoute(
    waypoints: NavigationWaypoint[],
    accessToken = (
        import.meta.env.VITE_MAPBOX_TOKEN as string | undefined
    )?.trim(),
): Promise<NavigationRouteResult> {
    if (!accessToken) throw new Error('Thiếu VITE_MAPBOX_TOKEN');
    if (waypoints.length < 2) throw new Error('Tuyến cần ít nhất hai điểm');

    const coordinates = waypoints
        .map((waypoint) => `${waypoint.lng},${waypoint.lat}`)
        .join(';');
    const params = new URLSearchParams({
        access_token: accessToken,
        geometries: 'geojson',
        overview: 'full',
        steps: 'true',
        language: 'vi',
    });
    const response = await fetch(
        `https://api.mapbox.com/directions/v5/mapbox/driving-traffic/${coordinates}?${params.toString()}`,
    );
    const payload = await response.json();
    const route = payload?.routes?.[0];

    if (!response.ok || payload?.code !== 'Ok' || !route?.geometry) {
        throw new Error(
            payload?.message || 'Mapbox không thể sinh tuyến đường',
        );
    }

    return {
        coordinates: route.geometry.coordinates,
        distanceMeters: Number(route.distance ?? 0),
        durationSeconds: Number(route.duration ?? 0),
        nextStopDistanceMeters: Number(route.legs?.[0]?.distance ?? 0),
        nextStopDurationSeconds: Number(route.legs?.[0]?.duration ?? 0),
    };
}
