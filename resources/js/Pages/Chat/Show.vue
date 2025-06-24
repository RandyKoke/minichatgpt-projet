<template>
    <AppLayout title="Conversation">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ conversation.title || 'Nouvelle conversation' }}
                </h2>
                <Link
                    :href="route('chat.index')"
                    class="text-indigo-600 hover:text-indigo-800"
                >
                    ← Retour aux conversations
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="flex flex-col h-[700px]">
                        <!-- Header avec sélecteur de modèle -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <ModelSelector
                                    v-model="selectedModel"
                                    :models="models"
                                    label="Modèle :"
                                    id="conversationModel"
                                />
                                <div class="text-sm text-gray-500">
                                    {{ conversation.messages.length }} message{{ conversation.messages.length > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>

                        <!-- Liste des messages -->
                        <MessagesList
                            :messages="conversation.messages"
                            :is-typing="isTyping"
                            :streaming-content="streamingContent"
                        />

                        <!-- Zone de saisie -->
                        <div class="p-4 border-t border-gray-200">
                            <form @submit.prevent="sendMessage" class="flex space-x-2">
                                <textarea
                                    v-model="newMessage"
                                    @keydown.enter.exact.prevent="sendMessage"
                                    @keydown.enter.shift.exact="addNewLine"
                                    rows="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                    placeholder="Tapez votre message... (Entrée pour envoyer, Shift+Entrée pour nouvelle ligne)"
                                    :disabled="!isReady"
                                ></textarea>
                                <button
                                    type="submit"
                                    :disabled="!isReady || !newMessage.trim()"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ModelSelector from '@/Components/Chat/ModelSelector.vue'
import MessagesList from '@/Components/Chat/MessagesList.vue'
import { useChat } from '@/Composables/useChat'

const props = defineProps({
    conversation: {
        type: Object,
        required: true
    },
    models: {
        type: Object,
        required: true
    }
})

const selectedModel = ref(props.conversation.model_used)
const newMessage = ref('')

const {
    processing,
    isTyping,
    streamingContent,
    isReady,
    streamMessage,
} = useChat()

const sendMessage = async () => {
    if (!newMessage.value.trim() || !isReady.value) return

    const message = newMessage.value
    newMessage.value = ''

    await streamMessage(message, props.conversation.id, selectedModel.value)
}

const addNewLine = () => {
    newMessage.value += '\n'
}
</script>
