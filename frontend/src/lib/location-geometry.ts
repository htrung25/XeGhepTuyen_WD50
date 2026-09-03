export type ServiceAreaBoundary =
    | { type: 'Polygon'; coordinates: number[][][] }
    | { type: 'MultiPolygon'; coordinates: number[][][][] };

function pointInRing(lng: number, lat: number, ring: number[][]): boolean {
    let inside = false;

    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i];
        const [xj, yj] = ring[j];
        const intersects =
            yi > lat !== yj > lat &&
            lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi;

        if (intersects) inside = !inside;
    }

    return inside;
}

function pointInPolygon(
    lng: number,
    lat: number,
    polygon: number[][][],
): boolean {
    const [outer, ...holes] = polygon;

    if (!outer || !pointInRing(lng, lat, outer)) return false;

    return !holes.some((hole) => pointInRing(lng, lat, hole));
}

export function isInsideBoundary(
    boundary: ServiceAreaBoundary | null | undefined,
    lng: number,
    lat: number,
): boolean {
    if (!boundary) return true;

    if (boundary.type === 'Polygon') {
        return pointInPolygon(lng, lat, boundary.coordinates as number[][][]);
    }

    return (boundary.coordinates as number[][][][]).some((polygon) =>
        pointInPolygon(lng, lat, polygon),
    );
}

export function boundaryCenter(
    boundary: ServiceAreaBoundary | null | undefined,
): [number, number] | null {
    if (!boundary) return null;

    const ring = (
        boundary.type === 'Polygon'
            ? boundary.coordinates[0]
            : boundary.coordinates[0]?.[0]
    ) as number[][] | undefined;

    if (!ring?.length) return null;

    const first = ring[0];
    const last = ring[ring.length - 1];
    const points =
        ring.length > 1 && first[0] === last[0] && first[1] === last[1]
            ? ring.slice(0, -1)
            : ring;

    const [lng, lat] = points.reduce(
        (sum, point) => [sum[0] + point[0], sum[1] + point[1]],
        [0, 0],
    );

    return [lng / points.length, lat / points.length];
}

export function boundaryBounds(
    boundary: ServiceAreaBoundary | null | undefined,
): [[number, number], [number, number]] | null {
    if (!boundary) return null;

    const polygons =
        boundary.type === 'Polygon'
            ? [boundary.coordinates as number[][][]]
            : (boundary.coordinates as number[][][][]);
    const points = polygons.flatMap((polygon) => polygon[0] ?? []);

    if (!points.length) return null;

    return points.reduce<[[number, number], [number, number]]>(
        (bounds, [lng, lat]) => [
            [Math.min(bounds[0][0], lng), Math.min(bounds[0][1], lat)],
            [Math.max(bounds[1][0], lng), Math.max(bounds[1][1], lat)],
        ],
        [
            [Number.POSITIVE_INFINITY, Number.POSITIVE_INFINITY],
            [Number.NEGATIVE_INFINITY, Number.NEGATIVE_INFINITY],
        ],
    );
}
