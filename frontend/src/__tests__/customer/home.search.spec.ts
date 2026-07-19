import { mount } from '@vue/test-utils';
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
        const vm = wrapper.vm as unknown as {
            fromCity: string;
            toCity: string;
            search: () => void;
        };

        vm.fromCity = 'Hà Nội';
        vm.toCity = 'Hà Nội';
        vm.search();

        expect(push).not.toHaveBeenCalled();
        expect(toastError).toHaveBeenCalled();
    });

    it('điều hướng sang /search khi điểm đi khác điểm đến', async () => {
        const wrapper = mountHome();
        const vm = wrapper.vm as unknown as {
            fromCity: string;
            toCity: string;
            search: () => void;
        };

        vm.fromCity = 'Hà Nội';
        vm.toCity = 'Hải Phòng';
        vm.search();

        expect(push).toHaveBeenCalledWith('/search');
        expect(toastError).not.toHaveBeenCalled();
    });
});
