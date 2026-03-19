<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" :key="`nil-${key}`" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded">
                    <span v-html="link.label"></span>
                </div>
                <Link v-else :key="`link-${key}`" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-[var(--accent-color)] focus:text-[var(--accent-color)]" :class="{ 'bg-[var(--accent-color)] text-white': link.active }" :href="link.url">
                    <span v-html="link.label"></span>
                </Link>
            </template>
        </div>
    </div>
</template>
