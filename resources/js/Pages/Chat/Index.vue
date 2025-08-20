<template>
    <AppLayout title="Mini ChatGPT">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mini ChatGPT
                </h2>

                <button
                    @click="showInstructionsModal = true"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                >
                    <div class="w-4 h-4 mr-2 border border-white rounded-full"></div>
                    Instructions
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Messages d'erreur -->
                <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
                            <button @click="errorMessage = ''" class="mt-1 text-xs text-red-600 hover:text-red-800">Fermer</button>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="flex h-[700px]">
                        <!-- Sidebar -->
                        <div class="w-1/3 bg-gray-50 border-r border-gray-200 overflow-y-auto">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Conversations</h3>

                                </div>

                                <ModelSelector
                                    v-model="selectedModel"
                                    :models="models || {}"
                                    label="Modèle"
                                />
                            </div>

                            <div class="p-4">
                                <ConversationList
                                    :conversations="conversations || []"
                                    :models="models || {}"
                                    :streaming-titles="streamingTitles"
                                />
                            </div>
                        </div>

                        <!-- Zone principale -->
                        <div class="flex-1 flex flex-col bg-white">
                            <div class="flex-1 overflow-y-auto p-6">
                                <div class="max-w-4xl mx-auto">
                                    <!-- Accueil -->
                                    <div v-if="!hasMessages" class="text-center py-16">
                                        <div class="text-6xl mb-6">💬</div>
                                        <h3 class="text-2xl font-semibold text-gray-900 mb-4">
                                            Bienvenue dans Mini ChatGPT
                                        </h3>
                                        <p class="text-gray-600 mb-8">
                                            Posez votre première question ci-dessous.
                                        </p>
                                    </div>

                                    <!-- Messages -->
                                    <div v-if="hasMessages" class="space-y-6">
                                        <div
                                            v-for="(message, index) in currentMessages"
                                            :key="message.id || index"
                                            :class="[
                                                'flex',
                                                message.role === 'user' ? 'justify-end' : 'justify-start'
                                            ]"
                                        >
                                            <div
                                                :class="[
                                                    'max-w-3xl px-6 py-4 rounded-xl shadow-sm',
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
                                            </div>
                                        </div>

                                        <!-- Streaming -->
                                        <div v-if="streamingContent" class="flex justify-start">
                                            <div class="bg-gray-100 text-gray-900 max-w-3xl px-6 py-4 rounded-xl shadow-sm">
                                                <div
                                                    class="prose prose-sm max-w-none"
                                                    v-html="formatMessage(streamingContent)"
                                                ></div>
                                                <div class="text-xs text-gray-500 mt-3 flex items-center">
                                                    <div class="animate-spin rounded-full h-3 w-3 border-2 border-gray-500 border-t-transparent mr-1"></div>
                                                    L'IA réfléchit...
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Zone de saisie -->
                            <div class="border-t border-gray-200 bg-white p-6">
                                <div class="max-w-4xl mx-auto">
                                    <form @submit.prevent="sendMessage" class="space-y-4">
                                        <div class="relative">
                                            <textarea
                                                v-model="newMessage"
                                                @keydown.enter.exact.prevent="sendMessage"
                                                @keydown.enter.shift.exact="newMessage += '\n'"
                                                rows="4"
                                                class="w-full px-6 py-4 text-lg border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                                placeholder="Tapez votre message..."
                                                :disabled="processing"
                                                style="min-height: 100px;"
                                            ></textarea>

                                            <div class="mt-3 flex items-center justify-between">
                                                <ModelSelector
                                                    v-model="selectedModel"
                                                    :models="models || {}"
                                                    label=""
                                                    class="text-sm"
                                                />

                                                <button
                                                    type="submit"
                                                    :disabled="processing || !newMessage.trim()"
                                                    class="bg-indigo-600 text-white px-6 py-3 rounded-xl hover:bg-indigo-700 disabled:opacity-50 flex items-center space-x-2"
                                                >
                                                    <div v-if="processing" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                                                    <div v-else class="w-5 h-5 border-2 border-white border-l-0 border-t-0 transform rotate-45"></div>
                                                    <span v-if="processing">Envoi...</span>
                                                    <span v-else>Envoyer</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <InstructionsModal
            :is-open="showInstructionsModal"
            @close="showInstructionsModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ModelSelector from '@/Components/Chat/ModelSelector.vue'
import ConversationList from '@/Components/Chat/ConversationList.vue'
import InstructionsModal from '@/Components/Modal/InstructionsModal.vue'
import { useMarkdown } from '@/Composables/useMarkdown'
import { useChatStreaming } from '@/Composables/useChatStreaming'

const props = defineProps({
    conversations: {
        type: Array,
        default: () => []
    },
    models: {
        type: Object,
        default: () => ({})
    }
})

const selectedModel = ref('gpt-3.5-turbo')
const showInstructionsModal = ref(false)
const newMessage = ref('')
const errorMessage = ref('')

const { formatMessage } = useMarkdown()
const {
    processing,
    streamingContent,
    streamingTitles,
    currentMessages,
    currentConversationId,
    startTitleStreaming,
    streamMessageResponse,
    createNewConversation
} = useChatStreaming()

const hasMessages = computed(() => currentMessages.value.length > 0)

// Bouton nouvelle - utilise Inertia pour une navigation toute simple
const createConversation = () => {
    if (processing.value) return

    router.post(route('conversations.store'), {
        model: selectedModel.value
    }, {
        onError: (errors) => {
            console.error('Erreur création conversation:', errors)
            errorMessage.value = 'Erreur lors de la création de la conversation'
        }
    })
}

// Envoi de message - utilisation de AJAX pour le streaming
const sendMessage = async () => {
    if (!newMessage.value.trim() || processing.value) return

    const message = newMessage.value.trim()

    const userMessage = {
        id: Date.now(),
        role: 'user',
        content: message,
        created_at: new Date()
    }

    currentMessages.value.push(userMessage)
    newMessage.value = ''
    processing.value = true
    errorMessage.value = ''

    try {
        if (!currentConversationId.value) {
            const conversationId = await createNewConversation(
                selectedModel.value,
                message,
                props.conversations
            )

            setTimeout(() => {
                startTitleStreaming(conversationId)
            }, 100)
        }

        await streamMessageResponse(message, currentConversationId.value, selectedModel.value)

    } catch (error) {
        console.error('Erreur:', error)
        currentMessages.value.pop()
        newMessage.value = message
        errorMessage.value = error.message || 'Erreur lors de l\'envoi du message'
    } finally {
        processing.value = false
    }
}
</script>
