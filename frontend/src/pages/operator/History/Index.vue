<script setup lang="ts">
import OperatorHistoryPanel from '@/components/operator/OperatorHistoryPanel.vue';
</script>

<template>
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">
                Lịch sử vận hành
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Toàn bộ hoạt động đặt chỗ, chuyến xe, tài xế và phương tiện của
                nhà xe.
            </p>
        </div>

        <OperatorHistoryPanel />
    </div>
</template>
