import { onScopeDispose, readonly, shallowReadonly, shallowRef } from 'vue';
import { boundaryCenter } from '@/lib/location-geometry';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

export interface PlaceSuggestion {
    id: string;
    name: string;
    address: string;
    secondaryText: string;
    coordinates: [number, number];
    category: 'address' | 'place';
}

interface MapboxFeature {
    id: string;
    type: string;
    text?: string;
    place_name?: string;
    address?: string;
    center?: [number, number];
    place_type?: string[];
}

interface UsePlaceSearchOptions {
    token?: string;
    cityBias?: () => string | undefined;
    boundary?: () => ServiceAreaBoundary | null | undefined;
}

const CITY_COORDS: Record<string, [number, number]> = {
    'Hà Nội': [105.8544, 21.0285],
    'Hải Phòng': [106.6881, 20.8449],
};

function toSuggestion(feature: MapboxFeature): PlaceSuggestion | null {
    if (!feature.center) return null;

    const label = feature.text?.trim() || 'Địa điểm';
    const name = feature.address ? `${feature.address} ${label}` : label;
    const address = feature.place_name?.trim() || name;
    const secondaryText = address.startsWith(name)
        ? address.slice(name.length).replace(/^,\s*/, '')
        : address;

    return {
        id: feature.id,
        name,
        address,
        secondaryText,
        coordinates: feature.center,
        category: feature.place_type?.includes('address') ? 'address' : 'place',
    };
}

export function usePlaceSearch(options: UsePlaceSearchOptions) {
    const suggestions = shallowRef<PlaceSuggestion[]>([]);
    const isSearching = shallowRef(false);
    const searchError = shallowRef('');
    const lastCompletedQuery = shallowRef('');
    let debounceTimer: ReturnType<typeof setTimeout> | null = null;
    let activeRequest: AbortController | null = null;

    function cancelPendingSearch(): void {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }

        activeRequest?.abort();
        activeRequest = null;
    }

    function clearSuggestions(): void {
        cancelPendingSearch();
        suggestions.value = [];
        searchError.value = '';
        lastCompletedQuery.value = '';
        isSearching.value = false;
    }

    function search(query: string): void {
        cancelPendingSearch();
        searchError.value = '';

        const normalizedQuery = query.trim();
        if (normalizedQuery.length < 2) {
            suggestions.value = [];
            lastCompletedQuery.value = '';
            isSearching.value = false;
            return;
        }

        isSearching.value = true;
        debounceTimer = setTimeout(async () => {
            if (!options.token) {
                searchError.value = 'Tìm kiếm địa điểm chưa được cấu hình.';
                suggestions.value = [];
                isSearching.value = false;
                return;
            }

            const controller = new AbortController();
            activeRequest = controller;
            const params = new URLSearchParams({
                access_token: options.token,
                country: 'vn',
                language: 'vi',
                limit: '8',
                autocomplete: 'true',
                fuzzyMatch: 'true',
                types: 'address,poi,place,locality,neighborhood',
            });
            const center =
                boundaryCenter(options.boundary?.()) ??
                CITY_COORDS[options.cityBias?.() ?? ''];

            if (center) params.set('proximity', center.join(','));

            try {
                const response = await fetch(
                    `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(normalizedQuery)}.json?${params.toString()}`,
                    { signal: controller.signal },
                );

                if (!response.ok) throw new Error('Mapbox search failed');

                const data = (await response.json()) as {
                    features?: MapboxFeature[];
                };
                suggestions.value = (data.features ?? [])
                    .map(toSuggestion)
                    .filter((item): item is PlaceSuggestion => item !== null);
                lastCompletedQuery.value = normalizedQuery;
            } catch (error) {
                if ((error as DOMException).name !== 'AbortError') {
                    suggestions.value = [];
                    searchError.value =
                        'Không thể tìm địa điểm. Vui lòng thử lại.';
                }
            } finally {
                if (activeRequest === controller) {
                    activeRequest = null;
                    isSearching.value = false;
                }
            }
        }, 300);
    }

    onScopeDispose(cancelPendingSearch);

    return {
        suggestions: shallowReadonly(suggestions),
        isSearching: readonly(isSearching),
        searchError: readonly(searchError),
        lastCompletedQuery: readonly(lastCompletedQuery),
        search,
        clearSuggestions,
    };
}
