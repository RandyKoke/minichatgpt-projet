<template>
    <AppLayout title="Exercice 1 - Question Simple">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Interface Simple de Question
                </h2>
                <Link
                    :href="route('chat.index')"
                    class="text-indigo-600 hover:text-indigo-800"
                >
                    ← Retour au Chat Principal
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <!-- Sélecteur de modèle -->
                        <ModelSelector
                            v-model="selectedModel"
                            :models="models || {}"
                            label="Choisir un modèle IA"
                        />

                        <!-- Formulaire de question -->
                        <form @submit.prevent="submitQuestion" class="mb-6">
                            <div class="mb-4">
                                <label for="question" class="block text-sm font-medium text-gray-700 mb-2">
                                    Votre question
                                </label>
                                <textarea
                                    id="question"
                                    v-model="question"
                                    rows="4"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Posez votre question ici..."
                                    required
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="processing || !question.trim()"
                                class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="processing">Génération en cours...</span>
                                <span v-else">Poser la question</span>
                            </button>
                        </form>

                        <!-- Protection des propriétés flash -->
                        <div v-if="$page.props.errors?.error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                            <p class="text-red-600">{{ $page.props.errors.error }}</p>
                        </div>

                        <!-- affichage sécurisé de la question et réponse -->
                        <div v-if="$page.props.flash?.question && $page.props.flash?.response" class="space-y-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-blue-800 mb-2">Votre question :</h3>
                                <p class="text-blue-700">{{ $page.props.flash.question }}</p>
                                <p class="text-sm text-blue-600 mt-2">
                                    Modèle utilisé : {{ getModelLabel($page.props.flash.model) }}
                                </p>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-green-800 mb-2">Réponse :</h3>
                                <div class="prose prose-sm max-w-none text-green-700">
                                    {{ $page.props.flash.response }}
                                </div>
                            </div>
                        </div>

                        <!-- Message s'il n'y a pas de résultat -->
                        <div v-else class="text-center py-8 text-gray-500">
                            <div class="text-4xl mb-4">On vous écoute...</div>
                            <p>Posez votre première question à l'IA pour débuter !</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ModelSelector from '@/Components/Chat/ModelSelector.vue'

const props = defineProps({
    models: {
        type: Object,
        default: () => ({})
    }
})

const selectedModel = ref('gpt-3.5-turbo')
const question = ref('')
const processing = ref(false)

const submitQuestion = () => {
    if (!question.value.trim()) return

    processing.value = true

    useForm({
        question: question.value,
        model: selectedModel.value,
    }).post(route('ask.send'), {
        onFinish: () => {
            processing.value = false
        },
    })
}

const getModelLabel = (modelValue) => {
    return props.models[modelValue] || modelValue
}
</script>
