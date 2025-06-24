<template>
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar">
        <div
            v-for="message in messages"
            :key="message.id"
            :class="[
                'flex',
                message.role === 'user' ? 'justify-end' : 'justify-start'
            ]"
        >
            <div
                :class="[
                    'max-w-3xl px-4 py-3 rounded-lg',
                    message.role === 'user'
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-100 text-gray-900'
                ]"
            >
                <div
                    v-if="message.role === 'assistant'"
                    class="prose prose-sm max-w-none"
                    v-html="formatMessage(message.content)"
                ></div>
                <div v-else class="whitespace-pre-wrap">{{ message.content }}</div>
                <div
                    :class="[
                        'text-xs mt-2 flex items-center justify-between',
                        message.role === 'user' ? 'text-indigo-200' : 'text-gray-500'
                    ]"
                >
                    <span>{{ formatTime(message.created_at) }}</span>
                    <span v-if="message.is_streaming" class="inline-flex items-center">
                        <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Streamé
                    </span>
                </div>
            </div>
        </div>

        <!-- Indicateur de frappe -->
        <div v-if="isTyping" class="flex justify-start">
            <div class="bg-gray-100 text-gray-900 max-w-3xl px-4 py-3 rounded-lg">
                <div class="flex items-center space-x-1">
                    <div class="flex space-x-1">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <span class="text-sm text-gray-500 ml-2">L'IA réfléchit...</span>
                </div>
            </div>
        </div>

        <!-- Message en cours de streaming -->
        <div v-if="streamingContent" class="flex justify-start">
            <div class="bg-gray-100 text-gray-900 max-w-3xl px-4 py-3 rounded-lg">
                <div
                    class="prose prose-sm max-w-none"
                    v-html="formatMessage(streamingContent)"
                ></div>
                <div class="text-xs text-gray-500 mt-2 flex items-center">
                    <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    streaming en cours...
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, nextTick, onMounted, watch } from 'vue'
import { useMarkdown } from '@/Composables/useMarkdown'

const props = defineProps({
    messages: {
        type: Array,
        required: true
    },
    isTyping: {
        type: Boolean,
        default: false
    },
    streamingContent: {
        type: String,
        default: ''
    }
})

const messagesContainer = ref(null)
const { formatMessage } = useMarkdown()

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

const formatTime = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Scroll vers le bas au chargement
onMounted(() => {
    nextTick(() => {
        scrollToBottom()
    })
})

// un watcher pour scroller quand de nouveaux messages arrivent
watch(() => props.messages.length, () => {
    nextTick(() => {
        scrollToBottom()
    })
})

watch(() => props.streamingContent, () => {
    nextTick(() => {
        scrollToBottom()
    })
})
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    @apply bg-gray-100;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-gray-300 rounded-full;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-400;
}
</style>
