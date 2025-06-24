<template>
    <AppLayout title="Mini ChatGPT - Page Principale">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Mini ChatGPT - Interface de Chat Complète
                </h2>

                <button
                    @click="openInstructionsModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >

                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Instructions Personnalisées
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Messages d'erreur améliorés -->
                <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
                        </div>
                        <div class="ml-auto pl-3">
                            <button @click="clearError" class="text-red-400 hover:text-red-600">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

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
                        <!-- Sidebar des conversations -->
                        <div class="w-1/3 bg-gray-50 border-r border-gray-200 overflow-y-auto">
                            <div class="p-4 border-b border-gray-200">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Conversations</h3>
                                    <button
                                        @click="createNewConversation"
                                        :disabled="processing"
                                        class="bg-indigo-600 text-white px-3 py-1 rounded-md text-sm hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    <!-- Zone d'accueil -->
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
                                                        'text-xs mt-3',
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

                                                    <svg class="animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    L'IA réfléchit...
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


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

                                                    <svg v-if="processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <!-- Icône envoi -->
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
const streamingTitles = ref({})
const isTitleStreaming = ref(false)
const errorMessage = ref('')

const { formatMessage } = useMarkdown()

const hasActiveConversation = computed(() => currentMessages.value.length > 0)

// fonction csrf renforcée
const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')
    if (!token) {
        console.error('Token CSRF manquant dans le DOM')
        return ''
    }
    const tokenValue = token.getAttribute('content')
    if (!tokenValue) {
        console.error('Valeur du token CSRF vide')
        return ''
    }
    return tokenValue
}

// On vérifie la validité du token
const isCSRFTokenValid = () => {
    const token = getCSRFToken()
    return token && token.length > 0
}

const clearError = () => {
    errorMessage.value = ''
}

const showError = (message) => {
    errorMessage.value = message
    setTimeout(() => {
        errorMessage.value = ''
    }, 10000) // efface automatiquement après 10 secondes
}

const createNewConversation = () => {
    if (!isCSRFTokenValid()) {
        showError('Token CSRF invalide. Veuillez rafraîchir la page.')
        return
    }

    router.post(route('conversations.store'), {
        model: selectedNewModel.value
    })
}

// envoi de message avec gestion d'erreurs renforcée
const sendMessage = async () => {
    if (!newMessage.value.trim() || processing.value) return

    // vérification CSRF au départ
    if (!isCSRFTokenValid()) {
        showError('Session expirée. Veuillez rafraîchir la page.')
        return
    }

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
    clearError()

    try {
        if (!currentConversationId.value) {
            console.log('Création d\'une nouvelle conversation...')

            const response = await fetch(route('conversations.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    model: selectedNewModel.value,
                    first_message: message
                }),
            })

            console.log('Réponse reçue:', response.status, response.statusText)

            if (!response.ok) {
                const errorText = await response.text()
                console.error('Erreur réponse:', response.status, errorText)

                if (response.status === 419) {
                    throw new Error('Session expirée. Veuillez rafraîchir la page.')
                } else if (response.status === 422) {
                    throw new Error('Données invalides. Vérifiez votre message.')
                } else {
                    throw new Error(`Erreur serveur (${response.status}): ${response.statusText}`)
                }
            }

            const data = await response.json()
            console.log('Données reçues:', data)

            if (data.success && data.conversation) {
                currentConversationId.value = data.conversation.id
                console.log('Conversation créée avec ID:', currentConversationId.value)

                // Ajouter la conversation à la liste
                const newConversation = {
                    id: data.conversation.id,
                    title: 'Nouvelle conversation',
                    model_used: selectedNewModel.value,
                    updated_at: new Date().toISOString()
                }

                props.conversations.unshift(newConversation)

                // Démarrer le streaming du titre
                startTitleStreamingInSidebar(currentConversationId.value)
            } else {
                throw new Error('Réponse invalide du serveur')
            }
        }

        // Streaming du message seulement si on a un ID de conversation valide
        if (currentConversationId.value) {
            await streamMessageResponse(message)
        } else {
            throw new Error('Impossible de créer la conversation')
        }

    } catch (error) {
        console.error('Erreur complète envoi message:', error)
        showError(error.message || 'Erreur lors de l\'envoi du message')

        // Retirer le message utilisateur en cas d'erreur
        currentMessages.value.pop()
        newMessage.value = message
    } finally {
        processing.value = false
        isTyping.value = false
    }
}

// stream titre avec vérif
const startTitleStreamingInSidebar = async (conversationId) => {
    if (!conversationId) {
        console.error('ID de conversation manquant pour le streaming du titre')
        return
    }

    if (!isCSRFTokenValid()) {
        console.error('Token CSRF invalide pour le streaming du titre')
        return
    }

    console.log('Démarrage streaming titre pour conversation:', conversationId)

    isTitleStreaming.value = true
    streamingTitles.value[conversationId] = ''

    try {
        const response = await fetch(route('messages.streamTitle', conversationId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/event-stream',
            },
        })

        if (!response.ok) {
            throw new Error(`Erreur streaming titre: ${response.status}`)
        }

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

                            setTimeout(() => {
                                delete streamingTitles.value[conversationId]
                            }, 2000)

                            isTitleStreaming.value = false
                        }
                        if (data.error) {
                            throw new Error(data.error)
                        }
                    } catch (e) {
                        console.warn('Erreur parsing JSON streaming titre:', e)
                    }
                }
            }
        }
    } catch (error) {
        console.error('Erreur streaming titre:', error)
        isTitleStreaming.value = false
        streamingTitles.value[conversationId] = 'Nouvelle conversation'

        const conversation = props.conversations.find(c => c.id === conversationId)
        if (conversation) {
            conversation.title = 'Nouvelle conversation'
        }
    }
}

// Streaming message avec vérif
const streamMessageResponse = async (message) => {
    if (!currentConversationId.value) {
        console.error('Pas d\'ID de conversation pour le streaming du message')
        throw new Error('ID de conversation manquant')
    }

    if (!isCSRFTokenValid()) {
        throw new Error('Token CSRF invalide pour le streaming')
    }

    console.log('Démarrage streaming message pour conversation:', currentConversationId.value)

    isTyping.value = false
    streamingContent.value = ''

    try {
        const response = await fetch(route('messages.stream', currentConversationId.value), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/event-stream',
            },
            body: JSON.stringify({
                message: message,
                model: selectedNewModel.value,
            }),
        })

        if (!response.ok) {
            throw new Error(`Erreur streaming: ${response.status}`)
        }

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
                        console.warn('Erreur parsing JSON streaming message:', e)
                    }
                }
            }
        }
    } catch (error) {
        console.error('Erreur streaming message:', error)
        streamingContent.value = ''
        throw error
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
