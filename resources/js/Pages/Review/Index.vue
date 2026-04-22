<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    deck: Object,
    card: Object,
    dueCount: Number,
    nextDue: String,
    allMode: { type: Boolean, default: false },
});

const cardHasImage = computed(() => props.card?.front_image_url || props.card?.back_image_url);

const showAnswer = ref(false);
const startTime = ref(Date.now());
const isSubmitting = ref(false);

const reviewForm = useForm({
    card_id: props.card?.id,
    deck_id: props.deck?.id,
    rating: null,
    duration_ms: null,
});

const boostForm = useForm({
    deck_id: props.allMode ? null : props.deck?.id,
});

const isBoosting = ref(false);

function requestBoost() {
    if (isBoosting.value) return;
    isBoosting.value = true;
    boostForm.post(route('review.boost'), {
        onFinish: () => { isBoosting.value = false; },
    });
}

function revealAnswer() {
    showAnswer.value = true;
}

function submitRating(rating) {
    if (isSubmitting.value) return;
    isSubmitting.value = true;

    reviewForm.rating = rating;
    reviewForm.duration_ms = Date.now() - startTime.value;
    reviewForm.card_id = props.card.id;
    reviewForm.deck_id = props.deck.id;

    const url = props.allMode
        ? route('review.submitAll')
        : route('review.submit', props.deck.id);

    reviewForm.post(url, {
        onFinish: () => {
            isSubmitting.value = false;
            showAnswer.value = false;
            startTime.value = Date.now();
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
                speakingText.value = null;
            }
        },
    });
}

// Keyboard shortcuts
function handleKeydown(e) {
    if (!props.card) return;

    if (!showAnswer.value) {
        if (e.code === 'Space' || e.code === 'Enter') {
            e.preventDefault();
            revealAnswer();
        }
        return;
    }

    if (e.key === '1') submitRating(1);
    else if (e.key === '2') submitRating(2);
    else if (e.key === '3') submitRating(3);
    else if (e.key === '4') submitRating(4);
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

function formatNextDue(dateStr) {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    return d.toLocaleString();
}

// Text-to-speech
const speakingText = ref(null);

function stripPhonetics(text) {
    // Remove parenthetical phonetic hints like "(chow boo-ee toy)" — whole lines or inline
    return text.replace(/\s*\([^)]*\)/g, '').trim();
}

function speak(text) {
    if (!window.speechSynthesis) return;

    // Toggle off if already speaking this text
    if (speakingText.value === text) {
        window.speechSynthesis.cancel();
        speakingText.value = null;
        return;
    }

    window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(stripPhonetics(text));
    utterance.lang = props.deck?.tts_language ?? 'vi-VN';

    // Use an explicit matching voice if available (may be empty on first call — that's OK,
    // lang is enough for the browser/OS to pick the right voice automatically)
    const langPrefix = utterance.lang.split('-')[0];
    const viVoice = window.speechSynthesis.getVoices().find(v => v.lang.startsWith(langPrefix));
    if (viVoice) utterance.voice = viVoice;

    utterance.onend = () => { speakingText.value = null; };
    utterance.onerror = () => { speakingText.value = null; };

    speakingText.value = text;
    window.speechSynthesis.speak(utterance);
}

const ratingButtons = [
    { rating: 1, label: 'Again', key: '1', color: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50 border-red-200 dark:border-red-800' },
    { rating: 2, label: 'Hard', key: '2', color: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-900/50 border-orange-200 dark:border-orange-800' },
    { rating: 3, label: 'Good', key: '3', color: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 border-blue-200 dark:border-blue-800' },
    { rating: 4, label: 'Easy', key: '4', color: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50 border-green-200 dark:border-green-800' },
];
</script>

<template>
    <AppLayout>
        <div :class="['px-4 sm:px-8 py-8 mx-auto', cardHasImage ? 'max-w-4xl' : 'max-w-2xl']">
            <!-- Header -->
            <div class="flex items-center gap-3 mb-6">
                <Link
                    :href="route('decks.index')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ allMode ? 'All Decks' : deck.name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <template v-if="allMode && deck">{{ deck.name }} · </template>{{ dueCount }} card{{ dueCount !== 1 ? 's' : '' }} remaining
                    </p>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-1.5 mb-8">
                <div
                    class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                    :style="{ width: dueCount > 0 ? '10%' : '100%' }"
                ></div>
            </div>

            <!-- All done state -->
            <div v-if="!card" class="text-center py-16">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">All done for today!</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-2">Great work on completing your reviews.</p>
                <p v-if="nextDue" class="text-sm text-gray-400 dark:text-gray-500 mb-6">
                    Next card due: {{ formatNextDue(nextDue) }}
                </p>
                <div class="flex items-center justify-center gap-3">
                    <Link
                        :href="route('decks.index')"
                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    >
                        Back to Decks
                    </Link>
                    <button
                        @click="requestBoost"
                        :disabled="isBoosting"
                        class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Boost (+5 cards)
                    </button>
                </div>
            </div>

            <!-- Review card -->
            <div v-else>
                <!-- Card face -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden mb-6">
                    <!-- Front -->
                    <div class="p-8">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Question</div>
                            <button
                                v-if="deck.tts_language"
                                @click="speak(card.front_content)"
                                :class="['p-1.5 rounded-lg transition-colors', speakingText === card.front_content ? 'text-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800']"
                                title="Speak (Vietnamese)"
                            >
                                <svg class="w-4 h-4" :class="{ 'animate-pulse': speakingText === card.front_content }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                </svg>
                            </button>
                        </div>
                        <div class="text-lg text-gray-900 dark:text-white leading-relaxed whitespace-pre-wrap">{{ card.front_content }}</div>
                        <div v-if="card.front_image_url" class="mt-4">
                            <img :src="card.front_image_url" alt="Front image" class="w-full rounded-lg" />
                        </div>
                    </div>

                    <!-- Answer reveal -->
                    <div v-if="showAnswer" class="border-t border-gray-200 dark:border-gray-800 p-8">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Answer</div>
                            <button
                                v-if="deck.tts_language"
                                @click="speak(card.back_content)"
                                :class="['p-1.5 rounded-lg transition-colors', speakingText === card.back_content ? 'text-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800']"
                                title="Speak (Vietnamese)"
                            >
                                <svg class="w-4 h-4" :class="{ 'animate-pulse': speakingText === card.back_content }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                </svg>
                            </button>
                        </div>
                        <div class="text-lg text-gray-900 dark:text-white leading-relaxed whitespace-pre-wrap">{{ card.back_content }}</div>
                        <div v-if="card.back_image_url" class="mt-4">
                            <img :src="card.back_image_url" alt="Back image" class="max-w-full rounded-lg max-h-64 object-contain" />
                        </div>
                    </div>
                </div>

                <!-- Show answer button -->
                <div v-if="!showAnswer" class="text-center">
                    <button
                        @click="revealAnswer"
                        class="bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 px-8 py-3 rounded-xl font-medium transition-colors"
                    >
                        Show Answer
                    </button>
                    <p class="text-xs text-gray-400 dark:text-gray-600 mt-2">Press Space or Enter</p>
                </div>

                <!-- Rating buttons -->
                <div v-else>
                    <div class="grid grid-cols-4 gap-3 mb-3">
                        <button
                            v-for="btn in ratingButtons"
                            :key="btn.rating"
                            @click="submitRating(btn.rating)"
                            :disabled="isSubmitting"
                            :class="['px-3 py-3 rounded-xl border text-sm font-semibold transition-colors disabled:opacity-50', btn.color]"
                        >
                            {{ btn.label }}
                        </button>
                    </div>
                    <div class="grid grid-cols-4 gap-3">
                        <p v-for="btn in ratingButtons" :key="btn.rating" class="text-center text-xs text-gray-400 dark:text-gray-600">
                            [{{ btn.key }}]
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
