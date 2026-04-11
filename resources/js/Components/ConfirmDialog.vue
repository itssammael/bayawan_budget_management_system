<script setup>
import Modal from './Modal.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    content: {
        type: String,
        required: true,
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    type: {
        type: String,
        default: 'warning', // 'warning', 'danger', 'info'
    },
    processing: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};

const getIconClass = () => {
    switch (props.type) {
        case 'danger': return 'bg-red-100 text-red-600';
        case 'warning': return 'bg-yellow-100 text-yellow-600';
        case 'info': return 'bg-blue-100 text-blue-600';
        default: return 'bg-yellow-100 text-yellow-600';
    }
};

const getButtonClass = () => {
    switch (props.type) {
        case 'danger': return 'bg-red-600 hover:bg-red-500 active:bg-red-900 border-transparent text-white focus:ring-red-500';
        case 'warning': return 'bg-yellow-600 hover:bg-yellow-500 active:bg-yellow-900 border-transparent text-white focus:ring-yellow-500';
        case 'info': return 'bg-blue-600 hover:bg-blue-500 active:bg-blue-900 border-transparent text-white focus:ring-blue-500';
        default: return '';
    }
};

</script>

<template>
    <Modal
        :show="show"
        max-width="md"
        :closeable="!processing"
        @close="close"
    >
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto shrink-0 flex items-center justify-center size-12 rounded-full sm:mx-0 sm:size-10" :class="getIconClass().split(' ')[0]">
                    <!-- Danger Icon -->
                    <svg v-if="type === 'danger'" class="size-6" :class="getIconClass().split(' ')[1]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <!-- Warning / Info Icon -->
                    <svg v-else class="size-6" :class="getIconClass().split(' ')[1]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </div>

                <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-start">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ title }}
                    </h3>

                    <div class="mt-2 text-sm text-gray-600">
                        {{ content }}
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-row justify-end space-x-3 px-6 py-4 bg-gray-100 text-end">
            <SecondaryButton @click="close" :disabled="processing">
                {{ cancelText }}
            </SecondaryButton>
            
            <!-- Default button logic if warning (primary-like) or fallback to custom classes -->
            <button 
                v-if="type === 'danger'"
                class="inline-flex items-center justify-center px-4 py-2 border font-semibold text-xs tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 rounded-md"
                :class="[getButtonClass(), { 'opacity-25': processing }]"
                @click="confirm"
                :disabled="processing"
            >
                {{ confirmText }}
            </button>
            <PrimaryButton 
                v-else
                @click="confirm"
                :disabled="processing"
            >
                {{ confirmText }}
            </PrimaryButton>
        </div>
    </Modal>
</template>
