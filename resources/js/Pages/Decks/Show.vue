<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    deck: Object,
    cards: Object, // paginated
});

// Infinite scroll — accumulate cards across pages
const allCards = ref([...props.cards.data]);
const hasMore = ref(props.cards.current_page < props.cards.last_page);
const loadingMore = ref(false);
const sentinel = ref(null);
let observer = null;

// When Inertia delivers a new page of cards, append them (reset on page 1)
watch(() => props.cards, (newCards) => {
    if (newCards.current_page === 1) {
        allCards.value = [...newCards.data];
    } else {
        allCards.value = [...allCards.value, ...newCards.data];
    }
    hasMore.value = newCards.current_page < newCards.last_page;
    loadingMore.value = false;
});

function loadMore() {
    if (loadingMore.value || !hasMore.value) return;
    loadingMore.value = true;
    router.get(
        route('cards.index', props.deck.id),
        { page: props.cards.current_page + 1 },
        { preserveState: true, preserveScroll: true, only: ['cards'] }
    );
}

onMounted(() => {
    window.addEventListener('keydown', handlePreviewKey);
    observer = new IntersectionObserver(
        (entries) => { if (entries[0].isIntersecting) loadMore(); },
        { rootMargin: '200px' }
    );
    // sentinel may not be in DOM yet if rendered inside v-else; watch for it
    watch(sentinel, (el) => {
        if (el) observer.observe(el);
    }, { immediate: true });
});

onUnmounted(() => {
    observer?.disconnect();
    window.removeEventListener('keydown', handlePreviewKey);
});

const showAddCardModal = ref(false);
const showEditCardModal = ref(false);
const showPreviewModal = ref(false);
const editingCard = ref(null);
const previewCard = ref(null);
const previewShowAnswer = ref(false);

function openPreviewModal(card) {
    previewCard.value = card;
    previewShowAnswer.value = false;
    showPreviewModal.value = true;
}

function handlePreviewKey(e) {
    if (!showPreviewModal.value) return;
    if (e.key === 'Escape') { showPreviewModal.value = false; return; }
    if (!previewShowAnswer.value && (e.code === 'Space' || e.code === 'Enter')) {
        e.preventDefault();
        previewShowAnswer.value = true;
    }
}

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
            <div class="flex items-center gap-3 sm:gap-4 mb-6">
                <Link
                    :href="route('decks.index')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <div v-if="deck.color" class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: deck.color }"></div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white truncate">{{ deck.name }}</h1>
                    </div>
                    <p v-if="deck.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ deck.description }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <Link
                        v-if="deck.is_active"
                        :href="route('review.index', deck.id)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        Review
                    </Link>
                    <button
                        @click="openAddCardModal"
                        class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Card</span>
                    </button>
                    <Link
                        :href="route('docs.api')"
                        class="flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        title="Use an AI agent to generate cards via the API"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <span class="hidden sm:inline">Use AI / API</span>
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="allCards.length === 0" class="text-center py-16">
                <div class="text-5xl mb-4">✏️</div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No cards yet</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Add your first card, or use your own AI agent to do it for you.</p>
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <button
                        @click="openAddCardModal"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        Add Card
                    </button>
                    <Link
                        :href="route('docs.api')"
                        class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-5 py-2 rounded-lg text-sm font-medium transition-colors"
                    >
                        Use AI / API →
                    </Link>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-6 max-w-md mx-auto">
                    Point Claude Code or another agent at this app's API and it will generate a full deck in seconds.
                </p>
            </div>

            <!-- Cards table -->
            <div v-else class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="text-left px-3 sm:px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Front</th>
                            <th class="text-left px-3 sm:px-4 py-3 font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Back</th>
                            <th class="text-left px-3 sm:px-4 py-3 font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">State</th>
                            <th class="text-left px-3 sm:px-4 py-3 font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell">Due</th>
                            <th class="px-2 sm:px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr
                            v-for="card in allCards"
                            :key="card.id"
                            :class="card.is_suspended ? 'opacity-50' : ''"
                            class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <td class="px-3 sm:px-4 py-3 max-w-[150px] sm:max-w-[200px] truncate text-gray-900 dark:text-white">
                                {{ card.front_content }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 max-w-[200px] truncate text-gray-600 dark:text-gray-400 hidden sm:table-cell">
                                {{ card.back_content }}
                            </td>
                            <td class="px-3 sm:px-4 py-3 hidden sm:table-cell">
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', stateClasses[card.fsrs_state]]">
                                    {{ stateLabels[card.fsrs_state] }}
                                </span>
                            </td>
                            <td class="px-3 sm:px-4 py-3 text-gray-500 dark:text-gray-400 text-xs hidden md:table-cell">
                                {{ formatDate(card.fsrs_due) }}
                            </td>
                            <td class="px-2 sm:px-4 py-3">
                                <div class="flex items-center justify-end gap-0.5 sm:gap-1">
                                    <button
                                        @click="openPreviewModal(card)"
                                        class="p-1 sm:p-1.5 rounded-md text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                        title="Preview"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openEditCardModal(card)"
                                        class="p-1 sm:p-1.5 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="suspendCard(card)"
                                        :title="card.is_suspended ? 'Unsuspend' : 'Suspend'"
                                        class="hidden sm:block p-1.5 rounded-md text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteCard(card)"
                                        class="p-1 sm:p-1.5 rounded-md text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
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

                <!-- Infinite scroll sentinel -->
                <div ref="sentinel" class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 text-center text-sm text-gray-400 dark:text-gray-600">
                    <span v-if="loadingMore">Loading…</span>
                </div>
            </div>
        </div>

        <!-- Preview Card Modal -->
        <Teleport to="body">
            <div v-if="showPreviewModal && previewCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showPreviewModal = false">
                <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl w-full max-w-lg">
                    <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Preview</span>
                        <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Front -->
                        <div>
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">Question</div>
                            <div class="text-base text-gray-900 dark:text-white leading-relaxed whitespace-pre-wrap">{{ previewCard.front_content }}</div>
                            <div v-if="previewCard.front_image_url" class="mt-3">
                                <img :src="previewCard.front_image_url" alt="Front image" class="max-w-full rounded-lg max-h-56 object-contain" />
                            </div>
                        </div>
                        <!-- Back -->
                        <div v-if="previewShowAnswer" class="border-t border-gray-200 dark:border-gray-800 pt-4">
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-2">Answer</div>
                            <div class="text-base text-gray-900 dark:text-white leading-relaxed whitespace-pre-wrap">{{ previewCard.back_content }}</div>
                            <div v-if="previewCard.back_image_url" class="mt-3">
                                <img :src="previewCard.back_image_url" alt="Back image" class="max-w-full rounded-lg max-h-56 object-contain" />
                            </div>
                        </div>
                        <div v-else class="pt-1">
                            <button
                                @click="previewShowAnswer = true"
                                class="w-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 py-2.5 rounded-xl text-sm font-medium transition-colors"
                            >
                                Show Answer
                            </button>
                            <p class="text-xs text-center text-gray-400 dark:text-gray-600 mt-1.5">Press Space or Enter</p>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

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
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
