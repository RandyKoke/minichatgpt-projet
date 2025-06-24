<template>
    <AppLayout title="Mini ChatGPT - Page Principale">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mini ChatGPT
                </h2>

                <button
                    @click="openInstructionsModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    Instructions Personnalisées
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    v-if="$page.props.flash && $page.props.flash.message"
                    class="mb-6 bg-green-50 border border-green-200 rounded-md p-4"
                >
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ $page.props.flash.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="flex h-[700px]">
                        <!-- Sidebar des conversations avec streaming des titres -->
                        <div class="w-1/3 bg-gray-50 border-r border-gray-200 overflow-y-auto">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Conversations</h3>
                                    <button
                                        @click="createNewConversation"
                                        class="bg-indigo-600 text-white px-3 py-1 rounded-md text-sm hover:bg-indigo-700"
                                    >
                                        Nouvelle
                                    </button>
                                </div>

                                <ModelSelector
                                    v-model="selectedNewModel"
                                    :models="models || {}"
                                    label="Modèle pour nouvelle conversation"
                                    id="newConversationModel"
                                />
                            </div>

                            <div class="p-4">
                                <!-- Le passage des titres streamés à la liste-->
                                <ConversationList
                                    :conversations="conversations || []"
                                    :models="models || {}"
                                    :streaming-titles="streamingTitles"
                                />
                            </div>
                        </div>

                        <!-- Zone principale avec le chat qui est bien intégré -->
                        <div class="flex-1 flex flex-col bg-white">
                            <!-- Zone de messages centrée et plus haute -->
                            <div class="flex-1 overflow-y-auto p-6">
                                <div class="max-w-4xl mx-auto">

                                    <div v-if="!hasActiveConversation" class="text-center py-16">
                                        <div class="text-6xl mb-6">💬</div>
                                        <h3 class="text-2xl font-semibold text-gray-900 mb-4">
                                            Bienvenue dans Mini ChatGPT
                                        </h3>
                                        <p class="text-gray-600 mb-8 leading-relaxed">
                                            Posez votre première question ci-dessous pour commencer une conversation.
                                        </p>
                                    </div>

                                    <!-- Messages de la conversation en cours -->
                                    <div v-if="currentMessages.length > 0" class="space-y-6">
                                        <div
                                            v-for="(message, index) in currentMessages"
                                            :key="message.id || `temp-${index}`"
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
                                                <div v-else class="whitespace-pre-wrap text-base">{{ message.content }}</div>
                                                <div
                                                    :class="[
                                                        'text-xs mt-3 flex items-center justify-between',
                                                        message.role === 'user' ? 'text-indigo-200' : 'text-gray-500'
                                                    ]"
                                                >
                                                    <span>{{ formatTime(message.created_at || new Date()) }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Message en cours de streaming -->
                                        <div v-if="streamingContent" class="flex justify-start">
                                            <div class="bg-gray-100 text-gray-900 max-w-3xl px-6 py-4 rounded-xl shadow-sm">
                                                <div
                                                    class="prose prose-sm max-w-none"
                                                    v-html="formatMessage(streamingContent)"
                                                ></div>
                                                <div class="text-xs text-gray-500 mt-3 flex items-center">
                                                    <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    L'IA réfléchit...
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Indicateur de frappe -->
                                        <div v-if="isTyping && !streamingContent" class="flex justify-start">
                                            <div class="bg-gray-100 text-gray-900 max-w-3xl px-6 py-4 rounded-xl shadow-sm">
                                                <div class="flex items-center space-x-2">
                                                    <div class="flex space-x-1">
                                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-500 ml-2">L'IA tape...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Zone de saisie que je place un peu plus haut -->
                            <div class="border-t border-gray-200 bg-white p-6">
                                <div class="max-w-4xl mx-auto">
                                    <form @submit.prevent="sendMessage" class="space-y-4">

                                        <div class="relative">
                                            <textarea
                                                v-model="newMessage"
                                                @keydown.enter.exact.prevent="sendMessage"
                                                @keydown.enter.shift.exact="addNewLine"
                                                rows="4"
                                                class="w-full px-6 py-4 text-lg border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                                placeholder="Tapez votre message... (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)"
                                                :disabled="processing"
                                                style="min-height: 100px; max-height: 200px;"
                                            ></textarea>

                                            <!-- Sélecteur de modèle et bouton d'envoi -->
                                            <div class="mt-3 flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <ModelSelector
                                                        v-model="selectedNewModel"
                                                        :models="models || {}"
                                                        label=""
                                                        class="text-sm"
                                                    />
                                                    <span class="text-sm text-gray-500">
                                                        {{ getModelLabel(selectedNewModel) }}
                                                    </span>
                                                </div>

                                                <button
                                                    type="submit"
                                                    :disabled="processing || !newMessage.trim()"
                                                    class="bg-indigo-600 text-white px-6 py-3 rounded-xl hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2 font-medium"
                                                >
                                                    <svg v-if="processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                    </svg>
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

        <!-- Modal -->
        <InstructionsModal
            :is-open="showInstructionsModal"
            @close="closeInstructionsModal"
            @saved="handleInstructionsSaved"
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

const selectedNewModel = ref('gpt-3.5-turbo')
const showInstructionsModal = ref(false)
const newMessage = ref('')
const processing = ref(false)
const isTyping = ref(false)
const streamingContent = ref('')
const currentMessages = ref([])
const currentConversationId = ref(null)

// Etat pour le streaming des titres dans le sidebar
const streamingTitles = ref({})
const isTitleStreaming = ref(false)

const { formatMessage } = useMarkdown()

const hasActiveConversation = computed(() => currentMessages.value.length > 0)

const createNewConversation = () => {
    router.post(route('conversations.store'), {
        model: selectedNewModel.value
    })
}


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
    isTyping.value = true

    try {
        if (!currentConversationId.value) {
            const response = await fetch(route('conversations.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                body: JSON.stringify({
                    model: selectedNewModel.value,
                    first_message: message
                }),
            })

            if (response.ok) {
                const data = await response.json()
                if (data.success) {
                    currentConversationId.value = data.conversation.id


                    const newConversation = {
                        id: data.conversation.id,
                        title: 'Nouvelle conversation',
                        model_used: selectedNewModel.value,
                        updated_at: new Date().toISOString()
                    }

                    // Ajouter en haut de la liste
                    props.conversations.unshift(newConversation)


                    startTitleStreamingInSidebar(currentConversationId.value)
                }
            }
        }

        await streamMessageResponse(message)

    } catch (error) {
        console.error('Erreur envoi message:', error)
        alert('Erreur lors de l\'envoi du message')
        currentMessages.value.pop()
        newMessage.value = message
    } finally {
        processing.value = false
        isTyping.value = false
    }
}

// Le streaming du titre en temps réel dans le sidebar
const startTitleStreamingInSidebar = async (conversationId) => {
    if (!conversationId) return

    isTitleStreaming.value = true
    streamingTitles.value[conversationId] = ''

    try {
        const response = await fetch(route('messages.streamTitle', conversationId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
        })

        const reader = response.body.getReader()
        let fullTitle = ''

        while (true) {
            const { done, value } = await reader.read()
            if (done) break

            const chunk = new TextDecoder().decode(value)
            const lines = chunk.split('\n')

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    try {
                        const data = JSON.parse(line.slice(6))
                        if (data.title) {

                            await new Promise(resolve => setTimeout(resolve, 300))
                            streamingTitles.value[conversationId] += data.title
                            fullTitle += data.title
                        }
                        if (data.done) {

                            const finalTitle = data.finalTitle || fullTitle.trim()
                            streamingTitles.value[conversationId] = finalTitle


                            const conversation = props.conversations.find(c => c.id === conversationId)
                            if (conversation) {
                                conversation.title = finalTitle
                            }

                            // On nettoie l'état de streaming après 2 secondes
                            setTimeout(() => {
                                delete streamingTitles.value[conversationId]
                            }, 2000)

                            isTitleStreaming.value = false
                        }
                        if (data.error) {
                            throw new Error(data.error)
                        }
                    } catch (e) {

                    }
                }
            }
        }
    } catch (error) {
        console.error('Erreur streaming titre:', error)
        isTitleStreaming.value = false
        streamingTitles.value[conversationId] = 'Nouvelle conversation'

        // Fallback vers le titre statique dans la liste
        const conversation = props.conversations.find(c => c.id === conversationId)
        if (conversation) {
            conversation.title = 'Nouvelle conversation'
        }
    }
}

const streamMessageResponse = async (message) => {
    isTyping.value = false
    streamingContent.value = ''

    try {
        const response = await fetch(route('messages.stream', currentConversationId.value), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            body: JSON.stringify({
                message: message,
                model: selectedNewModel.value,
            }),
        })

        const reader = response.body.getReader()
        let fullResponse = ''

        while (true) {
            const { done, value } = await reader.read()
            if (done) break

            const chunk = new TextDecoder().decode(value)
            const lines = chunk.split('\n')

            for (const line of lines) {
                if (line.startsWith('data: ')) {
                    try {
                        const data = JSON.parse(line.slice(6))
                        if (data.content) {
                            await new Promise(resolve => setTimeout(resolve, 120))
                            streamingContent.value += data.content
                            fullResponse += data.content
                        }
                        if (data.done) {
                            const assistantMessage = {
                                id: Date.now() + 1,
                                role: 'assistant',
                                content: fullResponse,
                                created_at: new Date()
                            }
                            currentMessages.value.push(assistantMessage)
                            streamingContent.value = ''
                        }
                        if (data.error) {
                            throw new Error(data.error)
                        }
                    } catch (e) {

                    }
                }
            }
        }
    } catch (error) {
        console.error('Erreur streaming:', error)
        streamingContent.value = ''
        alert('Une erreur est survenue lors de la génération de la réponse.')
    }
}

const addNewLine = () => {
    newMessage.value += '\n'
}

const openInstructionsModal = () => {
    showInstructionsModal.value = true
}

const closeInstructionsModal = () => {
    showInstructionsModal.value = false
}

const handleInstructionsSaved = () => {
    console.log('Instructions sauvegardées avec succès')
}

const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')
    return token ? token.getAttribute('content') : ''
}

const formatTime = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getModelLabel = (modelValue) => {
    return props.models[modelValue] || modelValue
}
</script>
