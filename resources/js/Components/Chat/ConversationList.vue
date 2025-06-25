<template>
    <div class="space-y-2">
        <div
            v-for="conversation in conversations"
            :key="conversation.id"
            class="group relative"
        >
            <Link
                :href="route('conversations.show', conversation)"
                class="block p-3 rounded-lg hover:bg-gray-100 transition-colors"
            >
                <!-- affichage du titre streamé en live -->
                <div class="font-medium text-gray-900 truncate">
                    <span>{{ getDisplayTitle(conversation) }}</span>
                    <!-- Curseur clignotant pendant le streaming -->
                    <span
                        v-if="streamingTitles[conversation.id] && isCurrentlyStreaming(conversation.id)"
                        class="inline-block w-1 h-4 bg-indigo-600 animate-pulse ml-1"
                    ></span>
                </div>

                <div class="text-sm text-gray-500 mt-1">
                    {{ formatDate(conversation.updated_at) }}
                </div>

                <div class="text-xs text-gray-400 mt-1">
                    {{ getModelLabel(conversation.model_used) }}
                </div>

                <!-- Afficher un aperçu du dernier message s'il est disponible -->
                <div v-if="conversation.latest_message" class="text-xs text-gray-400 mt-1 truncate">
                    {{ conversation.latest_message.content.substring(0, 50) }}...
                </div>
            </Link>

            <!-- Bouton de suppression -->
            <button
                @click.prevent="deleteConversation(conversation)"
                class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 transition-opacity"
                title="Supprimer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>

        <!-- Message si aucune conversation -->
        <div v-if="conversations.length === 0" class="text-gray-500 text-center py-8">
            Aucune conversation pour le moment.
            <br>
            Créez votre première conversation !
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    conversations: {
        type: Array,
        required: true
    },
    models: {
        type: Object,
        required: true
    },
    streamingTitles: {
        type: Object,
        default: () => ({})
    }
})


const getDisplayTitle = (conversation) => {
    // Si un titre est en cours de streaming pour cette conversation, on l'affiche
    if (props.streamingTitles[conversation.id]) {
        return props.streamingTitles[conversation.id]
    }

    // Sinon, on affiche le titre normal
    return conversation.title || 'Nouvelle conversation'
}

// on vérifie si le titre est bien streamé
const isCurrentlyStreaming = (conversationId) => {
    return props.streamingTitles[conversationId] &&
           props.streamingTitles[conversationId] !== '' &&
           !props.streamingTitles[conversationId].includes('conversation')
}

const deleteConversation = (conversation) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) {
        router.delete(route('conversations.destroy', conversation))
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = Math.abs(now - date)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays === 1) {
        return 'Aujourd\'hui'
    } else if (diffDays === 2) {
        return 'Hier'
    } else if (diffDays <= 7) {
        return `Il y a ${diffDays - 1} jours`
    } else {
        return date.toLocaleDateString('fr-FR')
    }
}

const getModelLabel = (modelValue) => {
    return props.models[modelValue] || modelValue
}
</script>

