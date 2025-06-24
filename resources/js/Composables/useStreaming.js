import { ref, onUnmounted } from 'vue'

export function useStreaming() {
    const isStreaming = ref(false)
    const streamContent = ref('')
    const streamError = ref(null)
    let eventSource = null

    const startStreaming = (url, onData, onComplete, onError) => {
        if (isStreaming.value) return

        isStreaming.value = true
        streamContent.value = ''
        streamError.value = null

        eventSource = new EventSource(url)

        eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data)

                if (data.content) {
                    streamContent.value += data.content
                    onData?.(data.content)
                }

                if (data.done) {
                    stopStreaming()
                    onComplete?.(streamContent.value)
                }

                if (data.error) {
                    throw new Error(data.error)
                }
            } catch (error) {
                handleError(error, onError)
            }
        }

        eventSource.onerror = (error) => {
            handleError(error, onError)
        }
    }

    const stopStreaming = () => {
        if (eventSource) {
            eventSource.close()
            eventSource = null
        }
        isStreaming.value = false
    }

    const handleError = (error, onError) => {
        streamError.value = error.message || 'Erreur de streaming'
        stopStreaming()
        onError?.(streamError.value)
    }

    onUnmounted(() => {
        stopStreaming()
    })

    return {
        isStreaming,
        streamContent,
        streamError,
        startStreaming,
        stopStreaming,
    }
}
