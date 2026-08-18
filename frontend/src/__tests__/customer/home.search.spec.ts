import { flushPromises, mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Home from '@/pages/customer/Home.vue';

// vi.mock được hoisted lên đầu file → spy phải khai báo qua vi.hoisted.
const { push, toastError, getPublicVouchers } = vi.hoisted(() => ({
    push: vi.fn(),
    toastError: vi.fn(),
    getPublicVouchers: vi.fn(),
}));

// Router giả — chỉ cần theo dõi push có được gọi hay không.
vi.mock('vue-router', () => ({
    useRouter: () => ({ push }),
}));

// Toast giả — tránh side effect UI, cho phép assert đã báo lỗi.
vi.mock('vue-sonner', () => ({
    toast: { error: toastError },
}));

vi.mock('@/api/geo.api', () => ({
    geoApi: {
        getProvinces: vi.fn().mockResolvedValue([
            {
                code: '01',
                name: 'Hà Nội',
                districts: [{ code: '005', name: 'Cầu Giấy' }],
            },
            {
                code: '31',
                name: 'Hải Phòng',
                districts: [{ code: '303', name: 'Hồng Bàng' }],
            },
        ]),
    },
}));

vi.mock('@/api/customer.api', () => ({
    customerApi: {
        getPublicVouchers,
    },
}));

// Store giả tối thiểu theo những gì Home.vue dùng.
vi.mock('@/stores/customer.store', () => ({
    useCustomerStore: () => ({
        getLocalDateString: () => '2026-07-19',
        searchParams: {},
    }),
}));

function mountHome() {
    return mount(Home, {
        global: { stubs: { 'router-link': true } },
    });
}

describe('Home search guard — điểm đi phải khác điểm đến', () => {
    beforeEach(() => {
        push.mockClear();
        toastError.mockClear();
        getPublicVouchers.mockReset();
        getPublicVouchers.mockResolvedValue({ data: [], error: null });
    });

    it('không điều hướng và báo lỗi khi điểm đi trùng điểm đến', async () => {
        const wrapper = mountHome();
        await flushPromises();
        (
            wrapper.vm as unknown as {
                searchPopular: (from: string, to: string) => void;
            }
        ).searchPopular('Hà Nội', 'Hà Nội');

        expect(push).not.toHaveBeenCalled();
        expect(toastError).toHaveBeenCalled();
    });

    it('điều hướng sang /search khi điểm đi khác điểm đến', async () => {
        const wrapper = mountHome();
        await flushPromises();
        (
            wrapper.vm as unknown as {
                searchPopular: (from: string, to: string) => void;
            }
        ).searchPopular('Hà Nội', 'Hải Phòng');

        expect(push).toHaveBeenCalledWith('/search');
        expect(toastError).not.toHaveBeenCalled();
    });

    it('đặt nút đổi chiều ở cột riêng, không phủ lên ô chọn', async () => {
        const wrapper = mountHome();
        await flushPromises();

        const swapButton = wrapper.get(
            'button[aria-label="Đổi điểm đón và điểm trả"]',
        );
        expect(swapButton.classes()).toContain('sm:col-start-2');
        expect(swapButton.classes()).not.toContain('absolute');
    });

    it('căn hàng ngày và hành khách theo cùng độ rộng với hai cột địa điểm', async () => {
        const wrapper = mountHome();
        await flushPromises();

        const dateField = wrapper.get('input[type="date"]');
        const detailGrid = dateField.element.closest('.grid');
        const passengerField = wrapper
            .get('button[aria-label="Tăng số hành khách"]')
            .element.closest('.grid > div');

        expect(detailGrid?.className).toContain(
            'sm:grid-cols-[minmax(0,1fr)_2.75rem_minmax(0,1fr)]',
        );
        expect(passengerField?.className).toContain('sm:col-start-3');
    });

    it('hiển thị voucher từ API và lịch trình có ảnh ngay phía sau', async () => {
        getPublicVouchers.mockResolvedValueOnce({
            error: null,
            data: [
                {
                    id: 'voucher-1',
                    code: 'NXDB20',
                    discount_type: 'percent',
                    discount_value: 20,
                    min_order: 100000,
                    max_discount: 50000,
                    valid_until: '2026-12-31T23:59:59+07:00',
                    operator: {
                        id: 'operator-1',
                        company_name: 'Nhà xe DB',
                    },
                },
            ],
        });

        const wrapper = mountHome();
        await flushPromises();

        expect(wrapper.text()).toContain('NXDB20');
        expect(wrapper.text()).toContain('Nhà xe DB');
        expect(
            wrapper.findAll('img[alt^="Hành trình "]').length,
        ).toBeGreaterThan(0);
        expect(wrapper.text().indexOf('Voucher nổi bật')).toBeLessThan(
            wrapper.text().indexOf('LỊCH TRÌNH NỔI BẬT'),
        );
    });
});
