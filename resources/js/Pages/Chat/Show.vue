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
                    ← Retour
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="flex flex-col h-[700px]">
                        <!-- Header -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <ModelSelector
                                    v-model="selectedModel"
                                    :models="models"
                                    label="Modèle :"
                                />
                                <div class="text-sm text-gray-500">
                                    {{ conversation.messages.length }} message{{ conversation.messages.length > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
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
                                    @keydown.enter.shift.exact="newMessage += '\n'"
                                    rows="1"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                                    placeholder="Tapez votre message..."
                                    :disabled="!isReady"
                                ></textarea>
                                <button
                                    type="submit"
                                    :disabled="!isReady || !newMessage.trim()"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <div v-if="processing" class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                                    <div v-else class="w-5 h-5 border-2 border-white border-l-0 border-t-0 transform rotate-45"></div>
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
</script>
