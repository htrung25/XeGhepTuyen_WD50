<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>

<template>
    <RouterLink
        to="/home"
        aria-label="Về trang chủ"
        class="absolute top-4 left-4 z-20 inline-flex min-h-11 items-center gap-2 rounded-xl border border-gray-200 bg-white/95 px-3 text-sm font-medium text-gray-700 shadow-sm backdrop-blur transition-colors hover:border-blue-200 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none sm:top-6 sm:left-6 lg:border-white/20 lg:bg-white/10 lg:text-white lg:hover:border-white/40 lg:hover:text-white"
    >
        <svg
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 19l-7-7 7-7"
            />
        </svg>
        <span class="hidden sm:inline">Về trang chủ</span>
    </RouterLink>
</template>
