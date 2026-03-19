<script setup>
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: [Number, String],
    placeholder: String,
});

const emit = defineEmits(['update:modelValue']);

const input = ref(null);
const displayValue = ref('');

const formatNumber = (val) => {
    if (val === null || val === undefined || val === '') return '';
    const num = parseFloat(val);
    if (isNaN(num)) return '';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(num);
};

const parseNumber = (val) => {
    if (!val) return 0;
    const clean = val.toString().replace(/,/g, '');
    const num = parseFloat(clean);
    return isNaN(num) ? 0 : num;
};

// Initial format
onMounted(() => {
    displayValue.value = formatNumber(props.modelValue);
});

// Sync from outside
watch(() => props.modelValue, (newVal) => {
    if (parseNumber(displayValue.value) !== parseNumber(newVal)) {
        displayValue.value = formatNumber(newVal);
    }
});

const handleInput = (e) => {
    let value = e.target.value;
    // Allow digits, one dot, and commas (commas will be stripped)
    // We update the model with the numeric value
    const numericValue = parseNumber(value);
    emit('update:modelValue', numericValue);
};

const handleBlur = () => {
    displayValue.value = formatNumber(props.modelValue);
};

const handleFocus = () => {
    if (props.modelValue == 0) {
        displayValue.value = '';
    } else {
        // When focusing, show raw number without commas for easier editing
        displayValue.value = props.modelValue.toString();
    }
};

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        type="text"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        v-model="displayValue"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        :placeholder="placeholder"
    >
</template>
