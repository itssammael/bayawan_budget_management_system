<script setup>
import { usePage } from '@inertiajs/vue3';
const page = usePage();
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="flex flex-col items-center">
            <slot name="logo" />
            <h1 v-if="page.props.system_settings?.org_name" class="mt-4 text-2xl font-bold text-gray-800 text-center px-4">
                {{ page.props.system_settings.org_name }}
            </h1>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
