<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    decks: Array,
    totalDue: Number,
    newPerDay: Object,
    newCardsToday: Number,
    dailyLimit: Number,
});

const totalActive = computed(() => props.decks.reduce((sum, d) => sum + (d.active_count ?? 0), 0));
const activeDecks = computed(() => props.decks.filter(d => d.is_active));
const inactiveDecks = computed(() => props.decks.filter(d => !d.is_active));

// Drag-to-reorder state
const dragSrcIndex = ref(null);
const dragOverIndex = ref(null);

function onDragStart(event, index) {
    dragSrcIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
}

function onDragOver(event, index) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    dragOverIndex.value = index;
}

function onDragLeave() {
    dragOverIndex.value = null;
}

function onDrop(event, dropIndex) {
    event.preventDefault();
    const fromIndex = dragSrcIndex.value;
    dragSrcIndex.value = null;
    dragOverIndex.value = null;

    if (fromIndex === null || fromIndex === dropIndex) return;

    // Reorder active decks locally
    const reordered = [...activeDecks.value];
    const [moved] = reordered.splice(fromIndex, 1);
    reordered.splice(dropIndex, 0, moved);

    // Persist: send all active deck IDs in new order, followed by inactive deck IDs
    const order = [
        ...reordered.map(d => d.id),
        ...inactiveDecks.value.map(d => d.id),
    ];

    router.post(route('decks.reorder'), { order }, { preserveScroll: true });
}

function onDragEnd() {
    dragSrcIndex.value = null;
    dragOverIndex.value = null;
}

const newPerDayEntries = computed(() => Object.entries(props.newPerDay ?? {}).map(([date, count]) => ({
    label: new Date(date + 'T00:00:00').toLocaleDateString('en', { weekday: 'short', month: 'short', day: 'numeric' }),
    count,
})));

const maxNewPerDay = computed(() => Math.max(...newPerDayEntries.value.map(e => e.count), 1));

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

// Starter deck ideas shown during onboarding (when user has no decks yet).
// Clicking one pre-fills the New Deck modal — they can edit before creating.
const starterDecks = [
    { emoji: '🌍', name: 'Country Flags', description: 'Match flags to countries around the world.', color: '#3b82f6' },
    { emoji: '🏛️', name: 'Capital Cities', description: 'Capitals of every country — a geography classic.', color: '#8b5cf6' },
    { emoji: '🇫🇷', name: 'French Vocabulary', description: 'Common French words and everyday phrases.', color: '#06b6d4' },
    { emoji: '🇪🇸', name: 'Spanish Vocabulary', description: 'Everyday Spanish words to build a base.', color: '#f97316' },
    { emoji: '🩺', name: 'Medical Conditions', description: 'Conditions, symptoms, and common treatments.', color: '#ef4444' },
    { emoji: '🧪', name: 'Periodic Table', description: 'Elements, symbols, and atomic numbers.', color: '#22c55e' },
    { emoji: '📐', name: 'Math Formulas', description: 'Formulas and identities worth knowing by heart.', color: '#eab308' },
    { emoji: '📖', name: 'Vocabulary Builder', description: 'Interesting words to add to your vocabulary.', color: '#ec4899' },
];

function useStarter(starter) {
    newDeckForm.reset();
    newDeckForm.name = starter.name;
    newDeckForm.description = starter.description;
    newDeckForm.color = starter.color;
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


const deckColors = [
    '', '#3b82f6', '#8b5cf6', '#ec4899', '#ef4444',
    '#f97316', '#eab308', '#22c55e', '#14b8a6', '#06b6d4',
];

const sparkTooltip = ref(null); // { deckId, index, value, x, y }

function onSparkMouseMove(event, deck) {
    const rect = event.currentTarget.getBoundingClientRect();
    const relX = event.clientX - rect.left;
    const fraction = relX / rect.width;
    const len = deck.mastered_trend.length;
    const index = Math.max(0, Math.min(Math.round(fraction * (len - 1)), len - 1));
    sparkTooltip.value = {
        deckId: deck.id,
        index,
        value: deck.mastered_trend[index],
        daysAgo: (len - 1) - index,
        x: event.clientX,
        y: event.clientY,
    };
}

function onSparkMouseLeave() {
    sparkTooltip.value = null;
}

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

            <!-- Stats bar -->
            <div v-if="decks.length > 0" class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5 mb-6">
                <div class="flex items-center gap-6 mb-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Active cards</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalActive }}</p>
                    </div>
                    <div v-if="totalDue > 0">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Due now</p>
                        <p class="text-2xl font-bold text-blue-600">{{ totalDue }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">New cards today</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ newCardsToday }} <span class="text-sm font-normal text-gray-400">/ {{ dailyLimit }}</span></p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-5">New cards introduced — last 7 days</p>
                <div class="flex items-end gap-1.5 h-20">
                    <div
                        v-for="entry in newPerDayEntries"
                        :key="entry.label"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ entry.count || '' }}</span>
                        <div
                            class="w-full rounded-t"
                            :class="entry.count > 0 ? 'bg-blue-500' : 'bg-gray-100 dark:bg-gray-800'"
                            :style="{ height: entry.count > 0 ? `${Math.max(8, Math.round((entry.count / maxNewPerDay) * 40))}px` : '4px' }"
                        ></div>
                        <span class="text-xs text-gray-400 dark:text-gray-500 truncate w-full text-center">{{ entry.label.split(',')[0] }}</span>
                    </div>
                </div>
            </div>

            <!-- Empty state / onboarding -->
            <div v-if="decks.length === 0" class="py-8">
                <div class="text-center mb-8">
                    <div class="text-5xl mb-4">📚</div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Create your first deck</h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        Pick an idea below to get started, or
                        <button
                            @click="openNewDeckModal"
                            class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
                        >
                            start from scratch
                        </button>.
                    </p>
                </div>

                <p class="text-xs uppercase tracking-wide font-medium text-gray-500 dark:text-gray-400 mb-3">
                    A few ideas to try
                </p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="starter in starterDecks"
                        :key="starter.name"
                        @click="useStarter(starter)"
                        type="button"
                        class="group flex items-start gap-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 text-left transition hover:border-blue-400 hover:shadow-md dark:hover:border-blue-500"
                    >
                        <div
                            class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-xl"
                            :style="{ backgroundColor: starter.color + '20' }"
                        >
                            {{ starter.emoji }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                {{ starter.name }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                {{ starter.description }}
                            </p>
                        </div>
                    </button>
                </div>

                <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
                    Picking an idea just pre-fills the name — you can edit everything before creating.
                </p>
            </div>

            <!-- Deck grid -->
            <div v-else>
            <div class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="(deck, index) in activeDecks"
                    :key="deck.id"
                    draggable="true"
                    @dragstart="onDragStart($event, index)"
                    @dragover="onDragOver($event, index)"
                    @dragleave="onDragLeave"
                    @drop="onDrop($event, index)"
                    @dragend="onDragEnd"
                    :class="[
                        'bg-white dark:bg-gray-900 rounded-xl border overflow-hidden transition-shadow',
                        dragOverIndex === index && dragSrcIndex !== index
                            ? 'border-blue-400 shadow-lg'
                            : 'border-gray-200 dark:border-gray-800 hover:shadow-md',
                        dragSrcIndex === index ? 'opacity-50' : '',
                    ]"
                >
                    <!-- Deck color bar -->
                    <div v-if="deck.color" class="h-1.5" :style="{ backgroundColor: deck.color }"></div>

                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2 flex-1 min-w-0 mr-3">
                                <!-- Drag handle -->
                                <div class="cursor-grab text-gray-300 dark:text-gray-600 hover:text-gray-400 flex-shrink-0" title="Drag to reorder">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 6a2 2 0 110-4 2 2 0 010 4zm8 0a2 2 0 110-4 2 2 0 010 4zM8 14a2 2 0 110-4 2 2 0 010 4zm8 0a2 2 0 110-4 2 2 0 010 4zM8 22a2 2 0 110-4 2 2 0 010 4zm8 0a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="font-semibold text-gray-900 dark:text-white truncate">{{ deck.name }}</h2>
                                    <p v-if="deck.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">
                                        {{ deck.description }}
                                    </p>
                                </div>
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

                        <!-- Progress bar -->
                        <div v-if="deck.cards_count > 0" class="mt-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                    {{ deck.active_count }}/{{ deck.cards_count }} active
                                    <span class="text-gray-400 dark:text-gray-500">({{ Math.round((deck.active_count / deck.cards_count) * 100) }}%)</span>
                                </span>
                                <span v-if="deck.due_count > 0" class="inline-flex items-center gap-1 text-xs font-medium">
                                    <span v-if="deck.review_due > 0" class="bg-blue-600 text-white px-1.5 py-0.5 rounded-full">{{ deck.review_due }} review</span>
                                    <span v-if="deck.new_due > 0" class="bg-emerald-600 text-white px-1.5 py-0.5 rounded-full">{{ deck.new_due }} new</span>
                                </span>
                            </div>
                            <div class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                                <div
                                    v-if="deck.mastered_count > 0"
                                    class="bg-green-500 transition-all duration-300"
                                    :style="{ width: (deck.mastered_count / deck.cards_count * 100) + '%' }"
                                    :title="`${deck.mastered_count} mastered`"
                                ></div>
                                <div
                                    v-if="deck.difficult_count > 0"
                                    class="bg-green-300 dark:bg-green-700 transition-all duration-300"
                                    :style="{ width: (deck.difficult_count / deck.cards_count * 100) + '%' }"
                                    :title="`${deck.difficult_count} review (difficult)`"
                                ></div>
                                <div
                                    v-if="deck.learning_count > 0"
                                    class="bg-orange-400 transition-all duration-300"
                                    :style="{ width: (deck.learning_count / deck.cards_count * 100) + '%' }"
                                    :title="`${deck.learning_count} learning`"
                                ></div>
                            </div>
                            <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                                <span v-if="deck.mastered_count > 0" class="flex items-center gap-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> {{ deck.mastered_count }} mastered
                                </span>
                                <span v-if="deck.difficult_count > 0" class="flex items-center gap-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-300 dark:bg-green-700"></span> {{ deck.difficult_count }} review
                                </span>
                                <span v-if="deck.learning_count > 0" class="flex items-center gap-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-orange-400"></span> {{ deck.learning_count }} learning
                                </span>
                                <span v-if="deck.cards_count - deck.active_count > 0" class="flex items-center gap-1">
                                    <span class="inline-block w-2 h-2 rounded-full bg-gray-200 dark:bg-gray-700"></span> {{ deck.cards_count - deck.active_count }} unseen
                                </span>
                            </div>
                        </div>
                        <!-- Mastered trend sparkline -->
                        <div v-if="deck.mastered_trend && Math.max(...deck.mastered_trend) > 0" class="mt-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Mastered — 30 days</p>
                            <svg
                                :viewBox="`0 0 ${deck.mastered_trend.length - 1} 30`"
                                class="w-full h-14 cursor-crosshair"
                                preserveAspectRatio="none"
                                @mousemove="onSparkMouseMove($event, deck)"
                                @mouseleave="onSparkMouseLeave"
                            >
                                <polygon
                                    :points="`0,30 ${deck.mastered_trend.map((v, i) => `${i},${30 - (v / Math.max(...deck.mastered_trend)) * 26}`).join(' ')} ${deck.mastered_trend.length - 1},30`"
                                    fill="#22c55e"
                                    fill-opacity="0.12"
                                />
                                <polyline
                                    :points="deck.mastered_trend.map((v, i) => `${i},${30 - (v / Math.max(...deck.mastered_trend)) * 26}`).join(' ')"
                                    fill="none"
                                    stroke="#22c55e"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    vector-effect="non-scaling-stroke"
                                />
                                <!-- Hover dot -->
                                <circle
                                    v-if="sparkTooltip && sparkTooltip.deckId === deck.id"
                                    :cx="sparkTooltip.index"
                                    :cy="30 - (deck.mastered_trend[sparkTooltip.index] / Math.max(...deck.mastered_trend)) * 26"
                                    r="2"
                                    fill="#22c55e"
                                    vector-effect="non-scaling-stroke"
                                />
                            </svg>
                        </div>

                        <div v-else-if="deck.cards_count === 0" class="mt-4">
                            <span class="text-sm text-gray-400 dark:text-gray-500">No cards yet</span>
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

            <template v-if="inactiveDecks.length > 0">
                <hr class="my-6 border-gray-200 dark:border-gray-800" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="deck in inactiveDecks"
                        :key="deck.id"
                        class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden opacity-60"
                    >
                        <div v-if="deck.color" class="h-1.5" :style="{ backgroundColor: deck.color }"></div>
                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0 mr-3">
                                    <h2 class="font-semibold text-gray-900 dark:text-white truncate">{{ deck.name }}</h2>
                                    <p v-if="deck.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ deck.description }}</p>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button @click="openEditDeckModal(deck)" class="p-1.5 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" title="Edit deck">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button @click="deleteDeck(deck)" class="p-1.5 rounded-md text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Delete deck">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <Link :href="route('cards.index', deck.id)" class="flex-1 text-center border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 px-3 py-2 rounded-lg text-sm font-medium transition-colors">Browse Cards</Link>
                                <button @click="toggleActive(deck)" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors border bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-500">Inactive</button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
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

        <!-- Sparkline tooltip -->
        <Teleport to="body">
            <div
                v-if="sparkTooltip"
                class="fixed z-50 pointer-events-none bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded shadow-lg whitespace-nowrap"
                :style="{ left: sparkTooltip.x + 12 + 'px', top: sparkTooltip.y - 32 + 'px' }"
            >
                {{ sparkTooltip.value }} mastered
                <span class="text-gray-400 ml-1">{{ sparkTooltip.daysAgo === 0 ? 'today' : `${sparkTooltip.daysAgo}d ago` }}</span>
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
