import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import LocationMapPicker from '@/components/customer/LocationMapPicker.vue';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

const mapState = vi.hoisted(() => ({
    center: { lng: 2, lat: 2 },
    handlers: new Map<string, () => void>(),
}));

vi.mock('mapbox-gl', () => {
    class FakeMap {
        constructor() {
            mapState.handlers.clear();
        }

        addControl() {}

        addSource() {}

        addLayer() {}

        getSource() {
            return undefined;
        }

        getCenter() {
            return mapState.center;
        }

        fitBounds() {}

        loaded() {
            return true;
        }

        remove() {}

        on(event: string, handler: () => void) {
            mapState.handlers.set(event, handler);
            if (event === 'load') queueMicrotask(handler);
        }
    }

    return {
        default: {
            accessToken: '',
            Map: FakeMap,
            NavigationControl: class {},
        },
    };
});

const boundary: ServiceAreaBoundary = {
    type: 'Polygon',
    coordinates: [
        [
            [0, 0],
            [1, 0],
            [1, 1],
            [0, 1],
            [0, 0],
        ],
    ],
};

describe('LocationMapPicker', () => {
    beforeEach(() => {
        mapState.center = { lng: 2, lat: 2 };
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue({
                ok: true,
                json: async () => ({
                    features: [{ place_name: 'Địa chỉ tại ghim' }],
                }),
            }),
        );
    });

    it('không che map, không đóng modal khi xác nhận ngoài vùng và cho kéo lại để xác nhận', async () => {
        const wrapper = mount(LocationMapPicker, {
            props: {
                token: 'test-token',
                initialCoords: [2, 2],
                boundary,
            },
        });

        await flushPromises();
        const confirmButton = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Xác nhận địa điểm'));

        expect(confirmButton).toBeDefined();
        await confirmButton!.trigger('click');

        expect(wrapper.emitted('confirm')).toBeUndefined();
        expect(wrapper.emitted('close')).toBeUndefined();
        expect(wrapper.text()).toContain('Điểm này nằm ngoài khu vực phục vụ');
        expect(wrapper.find('[role="dialog"]').exists()).toBe(true);
        expect(wrapper.find('.relative.flex-1.bg-slate-100').exists()).toBe(
            true,
        );

        mapState.center = { lng: 0.5, lat: 0.5 };
        mapState.handlers.get('movestart')?.();
        mapState.handlers.get('moveend')?.();
        await flushPromises();
        await confirmButton!.trigger('click');

        expect(wrapper.emitted('confirm')).toEqual([
            [
                {
                    address: 'Địa chỉ tại ghim',
                    lat: 0.5,
                    lng: 0.5,
                },
            ],
        ]);
    });
});
