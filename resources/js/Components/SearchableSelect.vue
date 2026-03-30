<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        required: true
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    label: {
        type: String,
        default: 'name'
    },
    value: {
        type: String,
        default: 'id'
    },
    error: {
        type: String,
        default: null
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const searchTerm = ref('');
const isOpen = ref(false);
const container = ref(null);
const searchInput = ref(null);

const selectedOption = computed(() => {
    return props.options.find(opt => opt[props.value] == props.modelValue);
});

const filteredOptions = computed(() => {
    if (!searchTerm.value) return props.options;
    return props.options.filter(opt => 
        opt[props.label].toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const selectOption = (option) => {
    emit('update:modelValue', option[props.value]);
    isOpen.value = false;
    searchTerm.value = '';
};

const toggleDropdown = () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchTerm.value = '';
        setTimeout(() => {
            if (searchInput.value) searchInput.value.focus();
        }, 0);
    }
};

const closeDropdown = (e) => {
    if (container.value && !container.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDropdown);
});
</script>

<template>
    <div ref="container" class="relative">
        <div 
            @click="toggleDropdown"
            class="w-full mt-1 border rounded-md shadow-sm px-3 py-2 bg-white flex justify-between items-center transition duration-150 ease-in-out"
            :class="[
                disabled ? 'bg-gray-50 cursor-not-allowed' : 'cursor-pointer',
                error 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                    : (disabled ? 'border-gray-200' : 'border-gray-300 focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)]')
            ]"
        >
            <span v-if="selectedOption" class="truncate text-sm">
                {{ selectedOption[label] }}
            </span>
            <span v-else class="text-gray-400 text-sm">
                {{ placeholder }}
            </span>
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </div>

        <div v-show="isOpen" class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200">
            <div class="p-2 border-b border-gray-100">
                <input 
                    ref="searchInput"
                    v-model="searchTerm"
                    type="text"
                    class="w-full text-sm border-gray-200 rounded-md focus:border-[var(--accent-color)] focus:ring-[var(--accent-color)]"
                    placeholder="Search..."
                    @click.stop
                >
            </div>
            <ul class="max-h-60 overflow-auto py-1">
                <li 
                    v-for="option in filteredOptions" 
                    :key="option[value]"
                    @click="selectOption(option)"
                    class="px-3 py-2 text-sm cursor-pointer transition duration-150 ease-in-out"
                    :class="[
                        option[value] == modelValue 
                            ? 'bg-[var(--accent-color-light)] text-[var(--accent-color-dark)] font-semibold' 
                            : 'hover:bg-gray-100'
                    ]"
                >
                    {{ option[label] }}
                </li>
                <li v-if="filteredOptions.length === 0" class="px-3 py-2 text-sm text-gray-500 italic text-center">
                    No results found
                </li>
            </ul>
        </div>
    </div>
</template>
