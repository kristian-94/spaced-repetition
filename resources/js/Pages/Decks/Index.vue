<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    decks: Array,
    totalDue: Number,
});

const showNewDeckModal = ref(false);
const showEditDeckModal = ref(false);
const editingDeck = ref(null);

const newDeckForm = useForm({
    name: '',
    description: '',
    color: '',
});

const editDeckForm = useForm({
    name: '',
    description: '',
    is_active: false,
    color: '',
    new_cards_per_day: 20,
    tts_language: '',
});

const ttsLanguages = [
    { value: '', label: 'Off' },
    { value: 'vi-VN', label: 'Vietnamese' },
    { value: 'zh-CN', label: 'Chinese (Mandarin)' },
    { value: 'zh-TW', label: 'Chinese (Traditional)' },
    { value: 'ja-JP', label: 'Japanese' },
    { value: 'ko-KR', label: 'Korean' },
    { value: 'fr-FR', label: 'French' },
    { value: 'de-DE', label: 'German' },
    { value: 'es-ES', label: 'Spanish' },
    { value: 'pt-BR', label: 'Portuguese (Brazil)' },
    { value: 'ru-RU', label: 'Russian' },
    { value: 'ar-SA', label: 'Arabic' },
];

function openNewDeckModal() {
    newDeckForm.reset();
    showNewDeckModal.value = true;
}

function openEditDeckModal(deck) {
    editingDeck.value = deck;
    editDeckForm.name = deck.name;
    editDeckForm.description = deck.description ?? '';
    editDeckForm.is_active = deck.is_active;
    editDeckForm.color = deck.color ?? '';
    editDeckForm.new_cards_per_day = deck.new_cards_per_day ?? 20;
    editDeckForm.tts_language = deck.tts_language ?? '';
    showEditDeckModal.value = true;
}

function createDeck() {
    newDeckForm.post(route('decks.store'), {
        onSuccess: () => {
            showNewDeckModal.value = false;
            newDeckForm.reset();
        },
    });
}

function updateDeck() {
    editDeckForm.patch(route('decks.update', editingDeck.value.id), {
        onSuccess: () => {
            showEditDeckModal.value = false;
        },
    });
}

function deleteDeck(deck) {
    if (!confirm(`Delete deck "${deck.name}"? All cards will be lost.`)) return;
    router.delete(route('decks.destroy', deck.id));
}

function toggleActive(deck) {
    router.patch(route('decks.toggleActive', deck.id));
}

const stateColors = {
    0: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400', // new
    1: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400', // learning
    2: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400', // review
    3: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400', // relearning
};

const deckColors = [
    '', '#3b82f6', '#8b5cf6', '#ec4899', '#ef4444',
    '#f97316', '#eab308', '#22c55e', '#14b8a6', '#06b6d4',
];
</script>

<template>
    <AppLayout>
        <div class="px-4 sm:px-8 py-8 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Decks</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ decks.length }} decks</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="totalDue > 0"
                        :href="route('review.all')"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        Review All
                        <span class="bg-green-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">{{ totalDue }}</span>
                    </Link>
                    <button
                        @click="openNewDeckModal"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Deck
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="decks.length === 0" class="text-center py-16">
                <div class="text-5xl mb-4">📚</div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No decks yet</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Create your first deck to start studying.</p>
                <button
                    @click="openNewDeckModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    Create Deck
                </button>
            </div>

            <!-- Deck grid -->
            <div v-else class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="deck in decks"
                    :key="deck.id"
                    class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden hover:shadow-md transition-shadow"
                >
                    <!-- Deck color bar -->
                    <div v-if="deck.color" class="h-1.5" :style="{ backgroundColor: deck.color }"></div>

                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0 mr-3">
                                <h2 class="font-semibold text-gray-900 dark:text-white truncate">{{ deck.name }}</h2>
                                <p v-if="deck.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                    {{ deck.description }}
                                </p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button
                                    @click="openEditDeckModal(deck)"
                                    class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                    title="Edit deck"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteDeck(deck)"
                                    class="p-1.5 rounded-md text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                    title="Delete deck"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Stats row -->
                        <div class="flex items-center gap-3 mt-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ deck.cards_count }} cards
                            </span>
                            <span
                                v-if="deck.due_count > 0"
                                class="inline-flex items-center gap-1 text-sm font-medium text-white bg-blue-600 px-2 py-0.5 rounded-full"
                            >
                                {{ deck.due_count }} due
                            </span>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <Link
                                v-if="deck.is_active"
                                :href="route('review.index', deck.id)"
                                class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            >
                                Review
                            </Link>
                            <Link
                                :href="route('cards.index', deck.id)"
                                class="flex-1 text-center border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            >
                                Browse Cards
                            </Link>
                            <button
                                @click="toggleActive(deck)"
                                :title="deck.is_active ? 'Deactivate deck' : 'Activate deck'"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm font-medium transition-colors border',
                                    deck.is_active
                                        ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400'
                                        : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-500'
                                ]"
                            >
                                {{ deck.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Deck Modal -->
        <Teleport to="body">
            <div v-if="showNewDeckModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">New Deck</h2>
                        <form @submit.prevent="createDeck" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                <input
                                    v-model="newDeckForm.name"
                                    type="text"
                                    required
                                    autofocus
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                    placeholder="e.g. French Vocabulary"
                                />
                                <p v-if="newDeckForm.errors.name" class="text-red-500 text-xs mt-1">{{ newDeckForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description <span class="text-gray-400">(optional)</span></label>
                                <textarea
                                    v-model="newDeckForm.description"
                                    rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm resize-none"
                                    placeholder="Optional description"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color <span class="text-gray-400">(optional)</span></label>
                                <div class="flex gap-2 flex-wrap">
                                    <button
                                        v-for="color in deckColors"
                                        :key="color"
                                        type="button"
                                        @click="newDeckForm.color = color"
                                        :class="[
                                            'w-6 h-6 rounded-full border-2 transition-all',
                                            newDeckForm.color === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent',
                                            color === '' ? 'bg-gray-200 dark:bg-gray-700' : ''
                                        ]"
                                        :style="color ? { backgroundColor: color } : {}"
                                    ></button>
                                </div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showNewDeckModal = false"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="newDeckForm.processing"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                                >
                                    Create
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Deck Modal -->
        <Teleport to="body">
            <div v-if="showEditDeckModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Deck</h2>
                        <form @submit.prevent="updateDeck" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                <input
                                    v-model="editDeckForm.name"
                                    type="text"
                                    required
                                    autofocus
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea
                                    v-model="editDeckForm.description"
                                    rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm resize-none"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                                <div class="flex gap-2 flex-wrap">
                                    <button
                                        v-for="color in deckColors"
                                        :key="color"
                                        type="button"
                                        @click="editDeckForm.color = color"
                                        :class="[
                                            'w-6 h-6 rounded-full border-2 transition-all',
                                            editDeckForm.color === color ? 'border-gray-900 dark:border-white scale-110' : 'border-transparent',
                                            color === '' ? 'bg-gray-200 dark:bg-gray-700' : ''
                                        ]"
                                        :style="color ? { backgroundColor: color } : {}"
                                    ></button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="editDeckForm.is_active"
                                    type="checkbox"
                                    id="editIsActive"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600"
                                />
                                <label for="editIsActive" class="text-sm text-gray-700 dark:text-gray-300">Active (show in review)</label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New cards per day</label>
                                <input
                                    v-model.number="editDeckForm.new_cards_per_day"
                                    type="number"
                                    min="1"
                                    max="9999"
                                    class="w-24 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Text-to-speech language</label>
                                <select
                                    v-model="editDeckForm.tts_language"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                >
                                    <option v-for="lang in ttsLanguages" :key="lang.value" :value="lang.value">{{ lang.label }}</option>
                                </select>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showEditDeckModal = false"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editDeckForm.processing"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                                >
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
