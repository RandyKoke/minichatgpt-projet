import { ref } from 'vue'

export function useChatStreaming() {
    const processing = ref(false)
    const isTyping = ref(false)
    const streamingContent = ref('')
    const streamingTitles = ref({})
    const currentMessages = ref([])
    const currentConversationId = ref(null)

    const getCSRFToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')
        return token ? token.getAttribute('content') : ''
    }

    const startTitleStreaming = async (conversationId) => {
        if (!conversationId) return

        streamingTitles.value[conversationId] = ''

        try {
            const response = await fetch(route('messages.streamTitle', conversationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })

            if (!response.ok) {
                throw new Error(`Erreur: ${response.status}`)
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

                                // Ne pas remplacer par "Nouvelle conversation" - garder le titre généré
                                if (finalTitle && finalTitle !== 'Nouvelle conversation') {
                                    streamingTitles.value[conversationId] = finalTitle

                                    // Mettre à jour le titre dans la liste des conversations
                                    const conversations = window.conversationsList || []
                                    const conversation = conversations.find(c => c.id === conversationId)
                                    if (conversation) {
                                        conversation.title = finalTitle
                                    }
                                }

                                setTimeout(() => {
                                    delete streamingTitles.value[conversationId]
                                }, 2000)
                            }
                        } catch (e) {
                            console.warn('Erreur parsing titre JSON:', e)
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Erreur streaming titre:', error)

            // Ici, one force plus le "Nouvelle conversation" - laisser le titre tel quel
            // ou on essaie de générer un titre simple à partir du premier message
            const conversations = window.conversationsList || []
            const conversation = conversations.find(c => c.id === conversationId)

            if (conversation && conversation.messages && conversation.messages.length > 0) {
                const firstMessage = conversation.messages[0].content
                const words = firstMessage.split(' ').slice(0, 3).join(' ')
                const fallbackTitle = words || 'Discussion'
                streamingTitles.value[conversationId] = fallbackTitle
                conversation.title = fallbackTitle
            }
        }
    }

    const streamMessageResponse = async (message, conversationId, model) => {
        isTyping.value = false
        streamingContent.value = ''

        try {
            const response = await fetch(route('messages.stream', conversationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message: message,
                    model: model,
                }),
            })

            if (!response.ok) {
                throw new Error(`Erreur: ${response.status}`)
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
                            console.warn('Erreur parsing message JSON:', e)
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

    const createNewConversation = async (model, firstMessage, conversations) => {
        try {
            const response = await fetch(route('conversations.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    model: model,
                    first_message: firstMessage
                }),
            })

            if (!response.ok) {
                throw new Error('Échec de création de conversation')
            }

            const data = await response.json()
            if (data.success) {
                currentConversationId.value = data.conversation.id

                const newConversation = {
                    id: data.conversation.id,
                    title: 'Nouvelle conversation',
                    model_used: model,
                    updated_at: new Date().toISOString(),
                    messages: []
                }

                conversations.unshift(newConversation)

                // Rendre la liste accessible globalement pour les mises à jour de titre
                window.conversationsList = conversations

                return data.conversation.id
            }

            throw new Error('Réponse invalide du serveur')
        } catch (error) {
            console.error('Erreur création conversation:', error)
            throw error
        }
    }

    return {
        processing,
        isTyping,
        streamingContent,
        streamingTitles,
        currentMessages,
        currentConversationId,
        startTitleStreaming,
        streamMessageResponse,
        createNewConversation
    }
}
