<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    telegram_chat_id: String,
    daily_new_cards_limit: Number,
    tokens: Array,
});

const page = usePage();
const newToken = ref(page.props.flash?.new_token ?? null);
const copied = ref(false);

const settingsForm = useForm({
    telegram_chat_id: props.telegram_chat_id ?? '',
    daily_new_cards_limit: props.daily_new_cards_limit ?? 20,
});

const tokenForm = useForm({
    name: '',
});

const testNotifForm = useForm({});

function saveSettings() {
    settingsForm.patch(route('settings.update'), {
        onSuccess: () => {},
    });
}

function generateToken() {
    tokenForm.post(route('settings.token.generate'), {
        onSuccess: (page) => {
            newToken.value = page.props.flash?.new_token ?? null;
            tokenForm.reset();
        },
    });
}

function revokeToken(tokenId) {
    if (!confirm('Revoke this token? It will stop working immediately.')) return;
    useForm({}).delete(route('settings.token.revoke', tokenId));
}

function copyToken() {
    if (!newToken.value) return;
    navigator.clipboard.writeText(newToken.value).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}

function sendTestNotification() {
    testNotifForm.post(route('settings.test-notification'));
}

function formatDate(dateStr) {
    if (!dateStr) return 'Never';
    return new Date(dateStr).toLocaleDateString();
}
</script>

<template>
    <AppLayout>
        <div class="px-4 sm:px-8 py-8 max-w-2xl mx-auto space-y-8">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>

            <!-- Flash messages -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3 text-sm text-green-700 dark:text-green-400">
                {{ $page.props.flash.success }}
            </div>

            <!-- New token display -->
            <div v-if="newToken" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300 mb-2">Your new API token (copy it now — it won't be shown again):</p>
                <div class="flex items-center gap-2">
                    <code class="flex-1 text-sm bg-white dark:bg-gray-800 border border-yellow-200 dark:border-yellow-700 rounded px-3 py-2 font-mono break-all text-gray-900 dark:text-white">
                        {{ newToken }}
                    </code>
                    <button
                        @click="copyToken"
                        class="flex-shrink-0 px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-sm font-medium transition-colors"
                    >
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>
            </div>

            <!-- Learning section -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Learning</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Cap how many new cards you see across all decks each day. Reviews of already-seen cards are not affected.
                </p>
                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            New cards per day (all decks combined)
                        </label>
                        <input
                            v-model.number="settingsForm.daily_new_cards_limit"
                            type="number"
                            min="1"
                            max="9999"
                            class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                        />
                        <p v-if="settingsForm.errors.daily_new_cards_limit" class="text-red-500 text-xs mt-1">{{ settingsForm.errors.daily_new_cards_limit }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="settingsForm.processing"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                    >
                        Save
                    </button>
                </form>
            </div>

            <!-- Telegram section -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Telegram Notifications</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Get daily reminders when cards are due. Send a message to <a href="https://t.me/kristian_claude_bot" target="_blank" class="text-blue-500 hover:underline">@kristian_claude_bot</a> on Telegram, then enter your Chat ID below.
                </p>
                <form @submit.prevent="saveSettings" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Telegram Chat ID
                        </label>
                        <input
                            v-model="settingsForm.telegram_chat_id"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                            placeholder="e.g. 123456789"
                        />
                        <p v-if="settingsForm.errors.telegram_chat_id" class="text-red-500 text-xs mt-1">{{ settingsForm.errors.telegram_chat_id }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="settingsForm.processing"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors"
                        >
                            Save
                        </button>
                        <button
                            v-if="telegram_chat_id"
                            type="button"
                            @click="sendTestNotification"
                            :disabled="testNotifForm.processing"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-50 rounded-lg text-sm font-medium transition-colors"
                        >
                            Test Notification
                        </button>
                    </div>
                    <div v-if="settingsForm.errors._error" class="text-red-500 text-sm">{{ settingsForm.errors._error }}</div>
                </form>
            </div>

            <!-- API Tokens section -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">API Tokens</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Use tokens to add cards via the API (e.g. from Claude or scripts).
                </p>

                <!-- Existing tokens -->
                <div v-if="tokens.length > 0" class="mb-4 space-y-2">
                    <div
                        v-for="token in tokens"
                        :key="token.id"
                        class="flex items-center justify-between py-2.5 px-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ token.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Created {{ formatDate(token.created_at) }} &middot;
                                Last used {{ formatDate(token.last_used_at) }}
                            </p>
                        </div>
                        <button
                            @click="revokeToken(token.id)"
                            class="text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium transition-colors"
                        >
                            Revoke
                        </button>
                    </div>
                </div>

                <!-- Generate token form -->
                <form @submit.prevent="generateToken" class="flex gap-3">
                    <input
                        v-model="tokenForm.name"
                        type="text"
                        required
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                        placeholder="Token name (e.g. Claude)"
                    />
                    <button
                        type="submit"
                        :disabled="tokenForm.processing"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex-shrink-0"
                    >
                        Generate
                    </button>
                </form>
                <p v-if="tokenForm.errors.name" class="text-red-500 text-xs mt-1">{{ tokenForm.errors.name }}</p>
            </div>
        </div>
    </AppLayout>
</template>
