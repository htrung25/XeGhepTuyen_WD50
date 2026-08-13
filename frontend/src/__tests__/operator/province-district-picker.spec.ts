import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/api/client', () => ({
    apiClient: {
        send: vi.fn(() =>
            Promise.resolve({
                data: [
                    {
                        code: '01',
                        name: 'Hà Nội',
                        districts: [
                            { code: '001', name: 'Quận Ba Đình' },
                            { code: '002', name: 'Quận Hoàn Kiếm' },
                        ],
                    },
                    {
                        code: '31',
                        name: 'Hải Phòng',
                        districts: [{ code: '303', name: 'Quận Hồng Bàng' }],
                    },
                ],
                meta: null,
                message: null,
                error: null,
            }),
        ),
    },
}));

import { apiClient } from '@/api/client';
import { geoApi } from '@/api/geo.api';
import ProvinceDistrictPicker from '@/components/operator/ProvinceDistrictPicker.vue';

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('ProvinceDistrictPicker', () => {
    beforeEach(() => {
        geoApi.clearCache();
        vi.clearAllMocks();
    });

    it('đổ danh sách huyện theo tỉnh đang chọn', async () => {
        const wrapper = mount(ProvinceDistrictPicker, {
            props: {
                label: 'Điểm đi',
                provinceCode: '01',
                districtCode: '',
            },
        });
        await flush();

        const districtOptions = wrapper
            .findAll('select')[1]
            .findAll('option')
            .map((o) => o.text());

        expect(districtOptions).toContain('Quận Ba Đình');
        expect(districtOptions).not.toContain('Quận Hồng Bàng');
    });

    it('đổi tỉnh thì reset huyện để không tạo cặp tỉnh–huyện không tồn tại', async () => {
        const wrapper = mount(ProvinceDistrictPicker, {
            props: {
                label: 'Điểm đi',
                provinceCode: '01',
                districtCode: '001',
            },
        });
        await flush();

        await wrapper.findAll('select')[0].setValue('31');

        expect(wrapper.emitted('update:provinceCode')?.[0]).toEqual(['31']);
        expect(wrapper.emitted('update:districtCode')?.[0]).toEqual(['']);
    });

    it('chỉ gọi API danh mục một lần cho cả phiên', async () => {
        await Promise.all([geoApi.getProvinces(), geoApi.getProvinces()]);
        await geoApi.getProvinces();

        expect(apiClient.send).toHaveBeenCalledTimes(1);
    });
});
