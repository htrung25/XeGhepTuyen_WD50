<script setup lang="ts">
import { Clock3 } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const now = ref(new Date());
let alignmentTimer: ReturnType<typeof setTimeout> | null = null;
let clockTimer: ReturnType<typeof setInterval> | null = null;

const time = computed(() =>
    new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).format(now.value),
);

const date = computed(() =>
    new Intl.DateTimeFormat('vi-VN', {
        timeZone: 'Asia/Ho_Chi_Minh',
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(now.value),
);

const isoDateTime = computed(() => now.value.toISOString());

function updateClock() {
    now.value = new Date();
}

onMounted(() => {
    updateClock();

    // Căn lần cập nhật đầu tiên vào đầu giây để đồng hồ không bị trôi nhịp.
    alignmentTimer = setTimeout(() => {
        updateClock();
        clockTimer = setInterval(updateClock, 1000);
    }, 1000 - new Date().getMilliseconds());
});

onUnmounted(() => {
    if (alignmentTimer) clearTimeout(alignmentTimer);
    if (clockTimer) clearInterval(clockTimer);
});
</script>

<template>
    <time
        :datetime="isoDateTime"
        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-slate-600"
        aria-label="Thời gian hiện tại theo giờ Việt Nam"
        title="Giờ Việt Nam (GMT+7)"
    >
        <Clock3 class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
        <span class="font-mono text-xs font-semibold tabular-nums sm:text-sm">
            {{ time }}
        </span>
        <span
            class="hidden border-l border-slate-200 pl-2 text-xs whitespace-nowrap text-slate-500 lg:inline"
        >
            {{ date }}
        </span>
    </time>
</template>
