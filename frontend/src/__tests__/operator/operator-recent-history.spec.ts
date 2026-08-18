import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import OperatorRecentHistory from '@/components/operator/OperatorRecentHistory.vue';

const { getHistory } = vi.hoisted(() => ({ getHistory: vi.fn() }));

vi.mock('@/api/operator.api', () => ({
    operatorApi: { getHistory },
}));

describe('OperatorRecentHistory', () => {
    it('chỉ yêu cầu và hiển thị 5 lịch sử mới nhất, không có bộ lọc', async () => {
        getHistory.mockResolvedValue({
            data: Array.from({ length: 6 }, (_, index) => ({
                id: `history-${index + 1}`,
                category: 'trip',
                action: 'trip_departed',
                severity: 'success',
                title: `Sự kiện ${index + 1}`,
                description: null,
                metadata: {
                    plate_number: `29A-0000${index + 1}`,
                    route: 'Hà Nội → Hải Phòng',
                },
                occurred_at: new Date().toISOString(),
            })),
            error: null,
        });

        const wrapper = mount(OperatorRecentHistory, {
            global: { stubs: { RouterLink: { template: '<a><slot /></a>' } } },
        });
        await flushPromises();

        expect(getHistory).toHaveBeenCalledWith({ per_page: 5 });
        expect(wrapper.findAll('ol > li')).toHaveLength(5);
        expect(wrapper.find('form').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('29A-00006');
    });
});
