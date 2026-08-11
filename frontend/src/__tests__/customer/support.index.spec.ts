import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import SupportIndex from '@/pages/customer/support/Index.vue';

const { createSupportTicket, getBookings, getSupportTickets, push } =
    vi.hoisted(() => ({
        createSupportTicket: vi.fn(),
        getBookings: vi.fn(),
        getSupportTickets: vi.fn(),
        push: vi.fn(),
    }));

vi.mock('vue-router', () => ({
    useRouter: () => ({ push }),
}));

vi.mock('@/api/customer.api', () => ({
    customerApi: {
        createSupportTicket,
        getBookings,
        getSupportTickets,
    },
}));

function mountSupportIndex() {
    return mount(SupportIndex, {
        global: { stubs: { 'router-link': true } },
    });
}

async function openCreateForm(wrapper: ReturnType<typeof mountSupportIndex>) {
    const createButton = wrapper
        .findAll('button')
        .find((button) => button.text().includes('Tạo yêu cầu mới'));
    await createButton?.trigger('click');
}

describe('customer support request form', () => {
    beforeEach(() => {
        vi.stubGlobal('localStorage', {
            clear: vi.fn(),
            getItem: vi.fn(),
            removeItem: vi.fn(),
            setItem: vi.fn(),
        });
        getSupportTickets.mockResolvedValue({
            data: [],
            meta: { current_page: 1, last_page: 1 },
            error: null,
        });
        getBookings.mockResolvedValue({ data: [], error: null });
        createSupportTicket.mockReset();
        push.mockReset();
    });

    it('cho phép click nút gửi và giải thích trường còn thiếu', async () => {
        const wrapper = mountSupportIndex();
        await flushPromises();
        await openCreateForm(wrapper);

        const submitButton = wrapper.get('button[type="submit"]');
        expect(submitButton.attributes('disabled')).toBeUndefined();

        await wrapper.get('form').trigger('submit');

        expect(wrapper.text()).toContain('Vui lòng chọn loại yêu cầu.');
        expect(createSupportTicket).not.toHaveBeenCalled();
    });

    it('gửi REST mutation khi form hợp lệ', async () => {
        createSupportTicket.mockResolvedValue({
            data: { id: 'ticket-1', ticket_code: 'TK-001' },
            error: null,
        });
        const wrapper = mountSupportIndex();
        await flushPromises();
        await openCreateForm(wrapper);

        await wrapper.get('input[type="radio"][value="general"]').setValue();
        await wrapper.get('#subject').setValue('Cần hỗ trợ');
        await wrapper
            .get('#body')
            .setValue('Nội dung yêu cầu hỗ trợ có đủ số ký tự.');
        await wrapper.get('form').trigger('submit');
        await flushPromises();

        expect(createSupportTicket).toHaveBeenCalledWith({
            category: 'general',
            subject: 'Cần hỗ trợ',
            message: 'Nội dung yêu cầu hỗ trợ có đủ số ký tự.',
            priority: 'normal',
        });
    });
});
