<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    deck: Object,
    cards: Object, // paginated
});

const showAddCardModal = ref(false);
const showEditCardModal = ref(false);
const editingCard = ref(null);

const addCardForm = useForm({
    front_content: '',
    back_content: '',
    front_image: null,
    back_image: null,
});

const editCardForm = useForm({
    front_content: '',
    back_content: '',
    front_image: null,
    back_image: null,
});

function openAddCardModal() {
    addCardForm.reset();
    showAddCardModal.value = true;
}

function openEditCardModal(card) {
    editingCard.value = card;
    editCardForm.front_content = card.front_content;
    editCardForm.back_content = card.back_content;
    showEditCardModal.value = true;
}

function addCard() {
    addCardForm.post(route('cards.store', props.deck.id), {
        forceFormData: true,
        onSuccess: () => {
            showAddCardModal.value = false;
            addCardForm.reset();
        },
    });
}

function updateCard() {
    editCardForm.patch(route('cards.update', editingCard.value.id), {
        forceFormData: true,
        onSuccess: () => {
            showEditCardModal.value = false;
        },
    });
}

function deleteCard(card) {
    if (!confirm('Delete this card?')) return;
    router.delete(route('cards.destroy', card.id));
}

function suspendCard(card) {
    router.patch(route('cards.suspend', card.id));
}

const stateLabels = { 0: 'New', 1: 'Learning', 2: 'Review', 3: 'Relearning' };
const stateClasses = {
    0: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
    1: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
    2: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    3: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
};

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const now = new Date();
    if (d <= now) return 'Due now';
    const diff = Math.ceil((d - now) / (1000 * 60 * 60 * 24));
    if (diff === 1) return 'Tomorrow';
    return `In ${diff} days`;
}
</script>

<template>
    <AppLayout>
        <div class="px-4 sm:px-8 py-8 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link
                    :href="route('decks.index')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <div v-if="deck.color" class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: deck.color }"></div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ deck.name }}</h1>
                    </div>
                    <p v-if="deck.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ deck.description }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="deck.is_active"
                        :href="route('review.index', deck.id)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        Review
                    </Link>
                    <button
                        @click="openAddCardModal"
                        class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Card
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="cards.data.length === 0" class="text-center py-16">
                <div class="text-5xl mb-4">✏️</div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No cards yet</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Add your first card to this deck.</p>
                <button
                    @click="openAddCardModal"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    Add Card
                </button>
            </div>

            <!-- Cards table -->
            <div v-else class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Front</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Back</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">State</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell">Due</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr
                            v-for="card in cards.data"
                            :key="card.id"
                            :class="card.is_suspended ? 'opacity-50' : ''"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <td class="px-4 py-3 max-w-[200px] truncate text-gray-900 dark:text-white">
                                {{ card.front_content }}
                            </td>
                            <td class="px-4 py-3 max-w-[200px] truncate text-gray-600 dark:text-gray-400 hidden sm:table-cell">
                                {{ card.back_content }}
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', stateClasses[card.fsrs_state]]">
                                    {{ stateLabels[card.fsrs_state] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs hidden md:table-cell">
                                {{ formatDate(card.fsrs_due) }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        @click="openEditCardModal(card)"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="suspendCard(card)"
                                        :title="card.is_suspended ? 'Unsuspend' : 'Suspend'"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteCard(card)"
                                        class="p-1.5 rounded-md text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        title="Delete"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="cards.last_page > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Page {{ cards.current_page }} of {{ cards.last_page }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="cards.prev_page_url"
                            :href="cards.prev_page_url"
                            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="cards.next_page_url"
                            :href="cards.next_page_url"
                            class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Card Modal -->
        <Teleport to="body">
            <div v-if="showAddCardModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add Card</h2>
                        <form @submit.prevent="addCard" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Front</label>
                                <textarea
                                    v-model="addCardForm.front_content"
                                    rows="3"
                                    required
                                    autofocus
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm resize-none"
                                    placeholder="Question or prompt"
                                ></textarea>
                                <p v-if="addCardForm.errors.front_content" class="text-red-500 text-xs mt-1">{{ addCardForm.errors.front_content }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Back</label>
                                <textarea
                                    v-model="addCardForm.back_content"
                                    rows="3"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm resize-none"
                                    placeholder="Answer"
                                ></textarea>
                                <p v-if="addCardForm.errors.back_content" class="text-red-500 text-xs mt-1">{{ addCardForm.errors.back_content }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Front Image <span class="text-gray-400">(optional)</span></label>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="addCardForm.front_image = $event.target.files[0]"
                                        class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-600 dark:file:text-gray-400"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Back Image <span class="text-gray-400">(optional)</span></label>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="addCardForm.back_image = $event.target.files[0]"
                                        class="w-full text-sm text-gray-600 dark:text-gray-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 dark:file:bg-gray-700 file:text-gray-600 dark:file:text-gray-400"
                                    />
                                </div>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showAddCardModal = false"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="addCardForm.processing"
                                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                                >
                                    Add Card
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Edit Card Modal -->
        <Teleport to="body">
            <div v-if="showEditCardModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Edit Card</h2>
                        <form @submit.prevent="updateCard" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Front</label>
                                <textarea
                                    v-model="editCardForm.front_content"
                                    rows="3"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm resize-none"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Back</label>
                                <textarea
                                    v-model="editCardForm.back_content"
                                    rows="3"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm resize-none"
                                ></textarea>
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="showEditCardModal = false"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editCardForm.processing"
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
