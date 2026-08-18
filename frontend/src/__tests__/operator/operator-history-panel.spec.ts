import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import OperatorHistoryPanel from '@/components/operator/OperatorHistoryPanel.vue';

const { getHistory } = vi.hoisted(() => ({ getHistory: vi.fn() }));

vi.mock('@/api/operator.api', () => ({
    operatorApi: { getHistory },
}));

describe('OperatorHistoryPanel', () => {
    beforeEach(() => {
        getHistory.mockReset();
        getHistory.mockResolvedValue({
            data: [
                {
                    id: 'history-1',
                    category: 'vehicle',
                    action: 'vehicle_issue_reported',
                    severity: 'danger',
                    title: 'Xe gặp vấn đề',
                    description: 'Xe 29A-12345 được báo nổ lốp.',
                    metadata: { plate_number: '29A-12345' },
                    actor: { id: 'driver-1', full_name: 'Nguyễn Văn A' },
                    occurred_at: '2026-08-18T03:00:00+07:00',
                },
            ],
            meta: { current_page: 1, last_page: 1, total: 1 },
            error: null,
        });
    });

    it('hiển thị lịch sử vận hành nhận từ API', async () => {
        const wrapper = mount(OperatorHistoryPanel);
        await flushPromises();

        expect(getHistory).toHaveBeenCalledWith({ page: 1, per_page: 8 });
        expect(wrapper.text()).toContain('Xe gặp vấn đề');
        expect(wrapper.text()).toContain('Xe 29A-12345 được báo nổ lốp.');
        expect(wrapper.text()).toContain('Thực hiện bởi Nguyễn Văn A');
    });

    it('gửi bộ lọc danh mục lên API', async () => {
        const wrapper = mount(OperatorHistoryPanel);
        await flushPromises();

        await wrapper.find('select').setValue('vehicle');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(getHistory).toHaveBeenLastCalledWith({
            category: 'vehicle',
            page: 1,
            per_page: 8,
        });
    });
});
