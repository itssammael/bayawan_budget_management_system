<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ToastItem from './ToastItem.vue';

const items = ref([]);

const addToast = (message, type = 'success') => {
    const id = Date.now();
    items.value.push({
        id,
        message,
        type,
    });
};

const removeToast = (id) => {
    const index = items.value.findIndex(item => item.id === id);
    if (index !== -1) {
        items.value.splice(index, 1);
    }
};

const page = usePage();

watch(() => page.props.flash, (flash) => {
    if (flash.success) {
        addToast(flash.success, 'success');
    }
    if (flash.error) {
        addToast(flash.error, 'error');
    }
    if (flash.warning) {
        addToast(flash.warning, 'warning');
    }
    if (flash.info) {
        addToast(flash.info, 'info');
    }
}, { deep: true, immediate: true });

</script>

<template>
    <div class="fixed top-20 right-4 z-[9999] flex flex-col gap-2 w-full max-w-xs">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-x-full opacity-0"
            enter-to-class="transform translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-x-0 opacity-100"
            leave-to-class="transform translate-x-full opacity-0"
        >
            <ToastItem
                v-for="item in items"
                :key="item.id"
                :message="item.message"
                :type="item.type"
                @remove="removeToast(item.id)"
            />
        </TransitionGroup>
    </div>
</template>
