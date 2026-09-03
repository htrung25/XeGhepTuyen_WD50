import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import LocationSearchBox from '@/components/customer/LocationSearchBox.vue';
import type { ServiceAreaBoundary } from '@/lib/location-geometry';

const boundary: ServiceAreaBoundary = {
    type: 'Polygon',
    coordinates: [
        [
            [105, 20],
            [107, 20],
            [107, 22],
            [105, 22],
            [105, 20],
        ],
    ],
};

describe('LocationSearchBox', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('tìm theo từ khóa, bias theo vùng phục vụ và trả địa điểm để mở map tinh chỉnh', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                features: [
                    {
                        id: 'poi.1',
                        text: 'Bệnh viện Việt Đức',
                        place_name:
                            'Bệnh viện Việt Đức, 40 Tràng Thi, Hoàn Kiếm, Hà Nội',
                        center: [105.847, 21.028],
                        place_type: ['poi'],
                    },
                ],
            }),
        });
        vi.stubGlobal('fetch', fetchMock);

        const wrapper = mount(LocationSearchBox, {
            props: {
                modelValue: '',
                placeholder: 'Tìm địa điểm',
                token: 'test-token',
                boundary,
                confirmed: false,
            },
        });

        const input = wrapper.get('input');
        await input.trigger('focus');
        expect(wrapper.text()).toContain('Vị trí hiện tại');
        await input.setValue('Bệnh viện');
        await wrapper.setProps({ modelValue: 'Bệnh viện' });
        await vi.advanceTimersByTimeAsync(300);
        await flushPromises();

        expect(fetchMock).toHaveBeenCalledOnce();
        const requestedUrl = String(fetchMock.mock.calls[0][0]);
        expect(requestedUrl).toContain('types=address%2Cpoi%2Cplace');
        expect(requestedUrl).toContain('proximity=106%2C21');
        expect(wrapper.text()).toContain('Bệnh viện Việt Đức');

        const result = wrapper
            .findAll('button')
            .find((button) => button.text().includes('Bệnh viện Việt Đức'));
        await result!.trigger('mousedown');

        expect(wrapper.emitted('select')).toEqual([
            [
                expect.objectContaining({
                    address:
                        'Bệnh viện Việt Đức, 40 Tràng Thi, Hoàn Kiếm, Hà Nội',
                    coordinates: [105.847, 21.028],
                }),
            ],
        ]);
    });
});
