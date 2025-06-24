<template>
    <AppLayout title="Exercice 3 - Instructions Personnalisées">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Exercice 3 : Instructions Personnalisées
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                Personnaliser votre Assistant IA
                            </h3>
                            <p class="text-gray-600">
                                Configurez le comportement de votre assistant en fournissant des informations sur vous
                                et en définissant comment vous souhaitez qu'il se comporte.
                            </p>
                        </div>

                        <!-- Affichage du message de succès -->
                        <div v-if="$page.props.flash.message" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                            <p class="text-green-600">{{ $page.props.flash.message }}</p>
                        </div>

                        <form @submit.prevent="saveInstructions" class="space-y-6">
                            <!-- Section "À propos de vous" -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <div class="flex items-start space-x-3 mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                            À propos de vous
                                        </h4>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Parlez-nous de vous pour que l'assistant puisse mieux vous aider.
                                        </p>
                                        <textarea
                                            v-model="form.about_you"
                                            rows="6"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Exemple : Je suis développeur web spécialisé en Laravel et Vue.js..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Section "Comportement de l'assistant" -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <div class="flex items-start space-x-3 mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                            Comportement de l'assistant
                                        </h4>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Définissez comment vous souhaitez que l'assistant se comporte et réponde.
                                        </p>
                                        <textarea
                                            v-model="form.assistant_behavior"
                                            rows="6"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Exemple : Réponds de manière professionnelle mais amicale..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle pour activer/désactiver, très important-->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">Activer les instructions personnalisées</h4>
                                    <p class="text-sm text-gray-600">Ces instructions seront appliquées à tous vos futurs chats.</p>
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

                            <!-- Boutons d'action -->
                            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                                <button
                                    type="button"
                                    @click="resetForm"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                                >
                                    Réinitialiser
                                </button>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    <span v-if="processing">Sauvegarde...</span>
                                    <span v-else>Sauvegarder</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    customInstruction: {
        type: Object,
        default: null
    }
})

const processing = ref(false)

const form = useForm({
    about_you: '',
    assistant_behavior: '',
    is_active: true,
})

const saveInstructions = () => {
    processing.value = true

    form.post(route('instructions.store'), {
        onFinish: () => {
            processing.value = false
        },
    })
}

const resetForm = () => {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le formulaire ?')) {
        form.reset()
        loadExistingData()
    }
}

const loadExistingData = () => {
    if (props.customInstruction) {
        form.about_you = props.customInstruction.about_you || ''
        form.assistant_behavior = props.customInstruction.assistant_behavior || ''
        form.is_active = props.customInstruction.is_active ?? true
    }
}

onMounted(() => {
    loadExistingData()
})
</script>
