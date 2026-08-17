import { flushPromises, mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import Home from '@/pages/customer/Home.vue';

// vi.mock được hoisted lên đầu file → spy phải khai báo qua vi.hoisted.
const { push, toastError } = vi.hoisted(() => ({
    push: vi.fn(),
    toastError: vi.fn(),
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
        getPublicVouchers: vi.fn().mockResolvedValue({ data: [] }),
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
});
