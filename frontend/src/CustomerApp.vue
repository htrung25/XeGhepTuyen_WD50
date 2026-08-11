<template>
    <router-view v-slot="{ Component }">
        <transition name="page-transition" mode="out-in">
            <component :is="Component" />
        </transition>
    </router-view>
</template>
