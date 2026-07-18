import '../css/app.css';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import AdminApp from '@/AdminApp.vue';
import { adminApi } from '@/api/admin.api';
import { validateStoredSession } from '@/api/client';
import adminRouter from '@/router/admin.router';

const app = createApp(AdminApp);
app.use(createPinia());
app.use(adminRouter);
app.mount('#app');

// Token cũ trong localStorage phải được server xác nhận, không tin mù quáng.
void validateStoredSession('admin', adminApi.me);
