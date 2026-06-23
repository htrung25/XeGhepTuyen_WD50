import { createPinia } from 'pinia';
import { createApp } from 'vue';
import AdminApp from '@/AdminApp.vue';
import adminRouter from '@/router/admin.router';

const app = createApp(AdminApp);
app.use(createPinia());
app.use(adminRouter);
app.mount('#app');
