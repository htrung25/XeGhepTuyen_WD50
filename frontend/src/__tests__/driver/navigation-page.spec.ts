import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { updateLocation } = vi.hoisted(() => ({
    updateLocation: vi.fn().mockResolvedValue({ error: null }),
}));

vi.mock('vue-router', () => ({
    useRoute: () => ({ params: { id: 'trip-1' } }),
}));

vi.mock('@/api/driver.api', () => ({
    driverApi: {
        getTrip: vi.fn().mockResolvedValue({
            data: {
                id: 'trip-1',
                route: { dest_city: 'Hải Phòng' },
                operator: { phone: '0901112233' },
            },
            error: null,
        }),
        getPassengers: vi.fn().mockResolvedValue({ data: [], error: null }),
        updateLocation,
    },
}));

vi.mock('@/components/MapboxMap.vue', () => ({
    default: { template: '<div data-test="map" />' },
}));

import Navigation from '@/pages/driver/trips/Navigation.vue';

describe('driver navigation GPS', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        updateLocation.mockClear();
        Object.defineProperty(navigator, 'geolocation', {
            configurable: true,
            value: {
                watchPosition: vi.fn().mockReturnValue(1),
                clearWatch: vi.fn(),
            },
        });
    });

    it('không gửi tọa độ Hà Nội giả trước khi trình duyệt trả GPS thật', async () => {
        const wrapper = mount(Navigation, {
            global: {
                plugins: [createPinia()],
                stubs: { RouterLink: true },
            },
        });

        await flushPromises();

        expect(updateLocation).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('Vui lòng cho phép truy cập GPS');
        expect(wrapper.find('a[href="tel:0901112233"]').exists()).toBe(true);

        wrapper.unmount();
    });
});
