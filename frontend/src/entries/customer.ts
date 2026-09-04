import '../css/app.css';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { validateStoredSession } from '@/api/client';
import { customerApi } from '@/api/customer.api';
import CustomerApp from '@/CustomerApp.vue';
import { installStaleBuildRecovery } from '@/lib/stale-build-recovery';
import customerRouter from '@/router/customer.router';

installStaleBuildRecovery();

const app = createApp(CustomerApp);
app.use(createPinia()).use(customerRouter).mount('#app');

// Token cũ trong localStorage phải được server xác nhận, không tin mù quáng.
void validateStoredSession('customer', customerApi.me);
