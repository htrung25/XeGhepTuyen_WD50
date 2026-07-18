import '../css/app.css';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { validateStoredSession } from '@/api/client';
import { operatorApi } from '@/api/operator.api';
import OperatorApp from '@/OperatorApp.vue';
import operatorRouter from '@/router/operator.router';

const app = createApp(OperatorApp);
app.use(createPinia());
app.use(operatorRouter);
app.mount('#app');

// Token cũ trong localStorage phải được server xác nhận, không tin mù quáng.
void validateStoredSession('operator', operatorApi.me);
