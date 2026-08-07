<script setup lang="ts">
import { SEAT_PATH } from './seatShape';
</script>

<template>
    <div class="relative h-14 w-12">
        <svg viewBox="0 0 48 56" class="h-full w-full">
            <path
                :d="SEAT_PATH"
                class="fill-gray-300 stroke-gray-400"
                stroke-width="2.5"
                stroke-linejoin="round"
            />
        </svg>
        <span
            class="pointer-events-none absolute inset-0 flex items-center justify-center pt-1 text-[9px] leading-tight font-medium text-gray-600"
        >
            Tài<br />xế
        </span>
    </div>
</template>
