import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import OperatorFleetMap from '@/components/operator/OperatorFleetMap.vue';

const { getDashboardMap } = vi.hoisted(() => ({
    getDashboardMap: vi.fn(),
}));

vi.mock('@/api/operator.api', () => ({
    operatorApi: { getDashboardMap },
}));

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: vi.fn() }),
}));

describe('OperatorFleetMap', () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    it('hiển thị thẻ trạng thái nhỏ gọn để không che nhiều diện tích bản đồ', async () => {
        getDashboardMap.mockResolvedValue({ data: [], error: null });

        const wrapper = mount(OperatorFleetMap, {
            global: {
                stubs: { MapboxMap: true, RouterLink: true },
            },
        });
        await flushPromises();

        const statusCard = wrapper.get(
            '[aria-label="Trạng thái định vị trực tuyến"]',
        );
        expect(statusCard.classes()).toContain('min-w-40');
        expect(statusCard.classes()).toContain('p-2.5');
        expect(statusCard.classes()).not.toContain('p-4');
        expect(statusCard.text()).toContain('0 xe đang trên đường');

        wrapper.unmount();
    });
});
