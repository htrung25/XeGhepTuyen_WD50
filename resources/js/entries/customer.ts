import { createPinia } from 'pinia';
import { createApp } from 'vue';
import CustomerApp from '@/CustomerApp.vue';
import customerRouter from '@/router/customer.router';

const app = createApp(CustomerApp);
app.use(createPinia()).use(customerRouter).mount('#app');
