import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useChat() {
    const processing = ref(false)
    const isTyping = ref(false)
    const streamingContent = ref('')
    const selectedModel = ref('gpt-3.5-turbo')

    const isReady = computed(() => !processing.value && !isTyping.value)

    const sendMessage = async (message, conversationId, model = null) => {
        if (!message.trim() || processing.value) return

        processing.value = true
        isTyping.value = true

        try {
            await router.post(route('messages.store', conversationId), {
                message: message,
                model: model || selectedModel.value,
            })
        } catch (error) {
            console.error('Erreur envoi message:', error)
        } finally {
            processing.value = false
            isTyping.value = false
        }
    }

    const streamMessage = async (message, conversationId, model = null) => {
        if (!message.trim() || processing.value) return

        processing.value = true
        isTyping.value = true
        streamingContent.value = ''

        try {
            const response = await fetch(route('messages.stream', conversationId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCSRFToken(),
                },
                body: JSON.stringify({
                    message: message,
                    model: model || selectedModel.value,
                }),
            })

            isTyping.value = false
            const reader = response.body.getReader()

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
                                // j'ai ajouté un délai supplémentaire ici pour que l'effet soit visible du côté frontend
                                await new Promise(resolve => setTimeout(resolve, 50))
                                streamingContent.value += data.content
                            }
                            if (data.done) {
                                router.reload({ only: ['conversation'] })
                                streamingContent.value = ''
                            }
                            if (data.error) {
                                throw new Error(data.error)
                            }
                        } catch (e) {
                            // On ignore les erreurs de parsing JSON pour continuer en cas de problème
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Erreur streaming:', error)
            alert('Une erreur est survenue lors de l\'envoi du message.')
        } finally {
            processing.value = false
            isTyping.value = false
            streamingContent.value = ''
        }
    }

    const getCSRFToken = () => {
        const token = document.querySelector('meta[name="csrf-token"]')
        return token ? token.getAttribute('content') : ''
    }

    return {
        processing,
        isTyping,
        streamingContent,
        selectedModel,
        isReady,
        sendMessage,
        streamMessage,
    }
}
