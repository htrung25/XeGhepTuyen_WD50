<script setup lang="ts">
import type { SeatInfo } from '@/stores/customer.store';
import { SEAT_PATH } from './seatShape';

const props = defineProps<{ seat: SeatInfo; selected: boolean }>();
defineEmits<{ toggle: [] }>();

function fillClasses() {
    if (props.seat.status === 'booked')
        return 'fill-red-100 stroke-red-200 cursor-not-allowed';
    if (props.seat.status === 'locked')
        return 'fill-yellow-100 stroke-yellow-300 cursor-not-allowed';
    if (props.selected) return 'fill-blue-600 stroke-blue-600 drop-shadow-md';
    return 'fill-white stroke-gray-300 hover:fill-blue-50 hover:stroke-blue-400 cursor-pointer';
}

function textClasses() {
    if (props.seat.status === 'booked') return 'text-red-400';
    if (props.seat.status === 'locked') return 'text-yellow-600';
    if (props.selected) return 'text-white';
    return 'text-gray-700';
}
</script>

<template>
    <button
        @click="$emit('toggle')"
        :disabled="seat.status !== 'available'"
        class="relative h-14 w-12 shrink-0 transition-transform active:scale-90"
    >
        <svg viewBox="0 0 48 56" class="h-full w-full">
            <path
                :d="SEAT_PATH"
                :class="['transition-colors', fillClasses()]"
                stroke-width="2.5"
                stroke-linejoin="round"
            />
        </svg>
        <span
            :class="[
                'pointer-events-none absolute inset-0 flex items-center justify-center pt-1 text-xs font-bold',
                textClasses(),
            ]"
        >
            {{ seat.seat_code }}
        </span>
    </button>
</template>
