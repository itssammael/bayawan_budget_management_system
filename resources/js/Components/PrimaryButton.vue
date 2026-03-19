<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-[var(--accent-color)] border border-transparent rounded-md font-semibold text-xs text-[var(--accent-contrast)] uppercase tracking-widest hover:opacity-90 focus:bg-[var(--accent-color)] active:bg-[var(--accent-color)] focus:outline-none focus:ring-2 focus:ring-[var(--accent-color)] focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
