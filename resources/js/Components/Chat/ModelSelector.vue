<template>
    <div class="mb-4">
        <label :for="id" class="block text-sm font-medium text-gray-700 mb-2">
            {{ label }}
        </label>
        <select
            :id="id"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
        >
            <option v-for="(label, value) in models" :key="value" :value="value">
                {{ label }}
            </option>
        </select>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        required: true
    },
    models: {
        type: Object,
        required: true
    },
    label: {
        type: String,
        default: 'Choisir un modèle IA'
    },
    id: {
        type: String,
        default: 'model-selector'
    }
})

defineEmits(['update:modelValue'])
</script>
