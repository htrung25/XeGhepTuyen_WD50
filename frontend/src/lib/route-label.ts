export interface RouteLabelData {
    origin_city?: string | null;
    origin_district?: string | null;
    dest_city?: string | null;
    dest_district?: string | null;
}

export function formatPlaceLabel(
    city?: string | null,
    district?: string | null,
    fallback = '—',
) {
    const cityName = city?.trim();
    const districtName = district?.trim();

    if (districtName && cityName) return `${districtName}, ${cityName}`;
    return districtName || cityName || fallback;
}

export function formatRouteLabel(
    route?: RouteLabelData | null,
    fallback = '—',
) {
    if (
        !route ||
        ![
            route.origin_city,
            route.origin_district,
            route.dest_city,
            route.dest_district,
        ].some((value) => value?.trim())
    ) {
        return fallback;
    }

    return `${formatPlaceLabel(route.origin_city, route.origin_district)} → ${formatPlaceLabel(route.dest_city, route.dest_district)}`;
}
