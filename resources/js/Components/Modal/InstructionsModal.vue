<template>
    <!-- Modal  -->
    <div
        v-show="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <!-- Background -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                v-show="isOpen"
                @click="closeModal"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                aria-hidden="true"
            ></div>

            <!-- Center modal -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal panel -->
            <div
                v-show="isOpen"
                class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
            >
                <!-- Header -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Instructions Personnalisées
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Configurez le comportement de votre assistant IA. Dites lui comment vous souhaitez qu'il réponde à vos requêtes.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeModal"
                        class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>

                <!-- Form Content avec v-else -->
                <div v-else>
                    <form @submit.prevent="saveInstructions" class="space-y-6">
                        <!-- Section "À propos de vous" -->
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-base font-semibold text-blue-900 mb-2">
                                        À propos de vous
                                    </h4>
                                    <p class="text-sm text-blue-700 mb-3">
                                        Exemple : Je suis un étudiant en informatique à l'IFOSUP de Wavre et je suis friand des réponses concises et professionnelles.
                                    </p>
                                    <textarea
                                        v-model="form.about_you"
                                        rows="4"
                                        class="w-full px-3 py-2 border border-blue-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="Décrivez votre profil, vos préférences..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Section "Comportement de l'assistant ou l'IA" -->
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-base font-semibold text-green-900 mb-2">
                                        Comportement de l'assistant
                                    </h4>
                                    <p class="text-sm text-green-700 mb-3">
                                        Exemple : Réponds de manière professionnelle et ajoute des émojis appropriés. Sois créatif et propose des alternatives.
                                    </p>
                                    <textarea
                                        v-model="form.assistant_behavior"
                                        rows="4"
                                        class="w-full px-3 py-2 border border-green-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 text-sm"
                                        placeholder="Définissez le style de réponse souhaité..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>


                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900">Activer les instructions personnalisées</h4>
                                <p class="text-sm text-gray-600">Ces instructions seront appliquées à tous vos futurs chats ou requêtes.</p>
                            </div>
                            <label class="inline-flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm text-gray-600">Actif</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                            >
                                <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span v-if="processing">Sauvegarde...</span>
                                <span v-else>Sauvegarder</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'saved'])

const loading = ref(false)
const processing = ref(false)

const form = ref({
    about_you: '',
    assistant_behavior: '',
    is_active: true,
})

const closeModal = () => {
    emit('close')
}

const loadExistingInstructions = async () => {
    if (!props.isOpen) return

    loading.value = true

    try {
        const response = await fetch(route('instructions.get'), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
        })

        if (response.ok) {
            const data = await response.json()
            if (data.customInstruction) {
                form.value = {
                    about_you: data.customInstruction.about_you || '',
                    assistant_behavior: data.customInstruction.assistant_behavior || '',
                    is_active: data.customInstruction.is_active ?? true,
                }
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement des instructions:', error)
    } finally {
        loading.value = false
    }
}

const saveInstructions = async () => {
    processing.value = true

    try {
        const response = await fetch(route('instructions.store.api'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCSRFToken(),
            },
            body: JSON.stringify(form.value),
        })

        if (response.ok) {
            const data = await response.json()
            if (data.success) {
                emit('saved')
                closeModal()
                alert('Instructions sauvegardées avec succès !')
            }
        } else {
            throw new Error('Erreur lors de la sauvegarde')
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        alert('Erreur lors de la sauvegarde des instructions')
    } finally {
        processing.value = false
    }
}

const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')
    return token ? token.getAttribute('content') : ''
}

</script>
