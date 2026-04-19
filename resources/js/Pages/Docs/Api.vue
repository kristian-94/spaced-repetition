<script setup>
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    baseUrl: {
        type: String,
        required: true,
    },
});

const page = usePage();
const isAuthed = !!page.props.auth?.user;

const copiedId = ref(null);

function copy(text, id) {
    navigator.clipboard.writeText(text).then(() => {
        copiedId.value = id;
        setTimeout(() => {
            if (copiedId.value === id) copiedId.value = null;
        }, 1500);
    });
}

const apiBase = `${props.baseUrl}/api`;

const agentPrompt = `You have access to a spaced repetition flashcard app via a REST API.

API base: ${apiBase}
Auth header: Authorization: Bearer <YOUR_TOKEN>
Docs: ${props.baseUrl}/docs/api

Workflow:
1. List existing decks:  GET ${apiBase}/decks
2. Create a deck if needed:  POST ${apiBase}/decks  with {"name": "..."}
3. Push cards in batches:  POST ${apiBase}/decks/{id}/cards
   with {"cards": [{"front_content": "...", "back_content": "..."}, ...]}

Please generate high-quality flashcards for the topic I give you and push
them to the right deck. Use concise front/back content (single concept per
card). Create a deck if one doesn't already fit.`;

const curlListDecks = `curl -H "Authorization: Bearer <YOUR_TOKEN>" \\
  ${apiBase}/decks`;

const curlCreateDeck = `curl -X POST \\
  -H "Authorization: Bearer <YOUR_TOKEN>" \\
  -H "Content-Type: application/json" \\
  -d '{"name": "French Vocab", "description": "French words"}' \\
  ${apiBase}/decks`;

const curlCreateCards = `curl -X POST \\
  -H "Authorization: Bearer <YOUR_TOKEN>" \\
  -H "Content-Type: application/json" \\
  -d '{"cards": [
    {"front_content": "Bonjour", "back_content": "Hello"},
    {"front_content": "Merci",   "back_content": "Thank you"}
  ]}' \\
  ${apiBase}/decks/{DECK_ID}/cards`;
</script>

<template>
    <Head title="API Docs" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">
        <header class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold">Spaced Repetition — API</h1>
                <div class="text-sm">
                    <Link v-if="isAuthed" :href="route('settings.show')" class="text-blue-600 dark:text-blue-400 hover:underline">
                        Settings
                    </Link>
                    <Link v-else :href="route('login')" class="text-blue-600 dark:text-blue-400 hover:underline">
                        Sign in
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 py-10 space-y-10">
            <section>
                <h2 class="text-2xl font-bold mb-3">Create flashcards with an AI agent</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    This app has a small REST API. Point any agent harness (Claude Code, a custom
                    script, whatever) at it and have the agent generate flashcards in bulk.
                </p>
                <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    <li>Sign in and generate an API token in <span class="font-medium">Settings → API Tokens</span>.</li>
                    <li>Copy the agent prompt below into your harness (replace <code>&lt;YOUR_TOKEN&gt;</code>).</li>
                    <li>Ask it to build a deck on any topic.</li>
                </ol>
            </section>

            <section>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-base font-semibold">Agent prompt</h3>
                    <button
                        @click="copy(agentPrompt, 'prompt')"
                        class="text-xs px-2 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white font-medium transition-colors"
                    >
                        {{ copiedId === 'prompt' ? 'Copied!' : 'Copy' }}
                    </button>
                </div>
                <pre class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-4 overflow-x-auto whitespace-pre-wrap">{{ agentPrompt }}</pre>
            </section>

            <section>
                <h3 class="text-base font-semibold mb-2">Authentication</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    All endpoints require a bearer token. Generate one from
                    <Link v-if="isAuthed" :href="route('settings.show')" class="text-blue-600 dark:text-blue-400 hover:underline">Settings</Link>
                    <span v-else>Settings (after signing in)</span>.
                    Tokens are shown once — store them somewhere safe.
                </p>
                <pre class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-3 overflow-x-auto">Authorization: Bearer &lt;YOUR_TOKEN&gt;</pre>
            </section>

            <section>
                <h3 class="text-base font-semibold mb-2">Endpoints</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                        <thead class="bg-gray-100 dark:bg-gray-800">
                            <tr>
                                <th class="text-left px-3 py-2 font-medium">Method</th>
                                <th class="text-left px-3 py-2 font-medium">Path</th>
                                <th class="text-left px-3 py-2 font-medium">What it does</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">GET</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/decks</td>
                                <td class="px-3 py-2">List your decks with due-card counts.</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">POST</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/decks</td>
                                <td class="px-3 py-2">Create a deck. Body: <code>{name, description?, color?}</code>.</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">GET</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/decks/{id}/cards</td>
                                <td class="px-3 py-2">List cards in a deck (paginated).</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">POST</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/decks/{id}/cards</td>
                                <td class="px-3 py-2">Batch-create or upsert cards. Body: <code>{cards: [...]}</code>.</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">PUT</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/cards/{id}</td>
                                <td class="px-3 py-2">Update a card's front/back.</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">DELETE</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/cards/{id}</td>
                                <td class="px-3 py-2">Delete a card.</td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">POST</td>
                                <td class="px-3 py-2 font-mono text-xs">/api/cards/{id}/suspend</td>
                                <td class="px-3 py-2">Toggle a card's suspended state.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section>
                <h3 class="text-base font-semibold mb-2">Card payload</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    Each card takes <code>front_content</code> and <code>back_content</code> (plain
                    text or HTML). Short aliases <code>front</code> / <code>back</code> also work.
                    Optional fields: <code>front_image_url</code>, <code>back_image_url</code>,
                    <code>front_image_base64</code>, <code>back_image_base64</code>.
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Upserts happen automatically: posting a card with the same
                    <code>front_content</code> (and image URL, if any) in the same deck updates it
                    rather than creating a duplicate.
                </p>
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-semibold">Examples</h3>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-medium">List decks</p>
                        <button
                            @click="copy(curlListDecks, 'list')"
                            class="text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors"
                        >
                            {{ copiedId === 'list' ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>
                    <pre class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-3 overflow-x-auto">{{ curlListDecks }}</pre>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-medium">Create a deck</p>
                        <button
                            @click="copy(curlCreateDeck, 'create-deck')"
                            class="text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors"
                        >
                            {{ copiedId === 'create-deck' ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>
                    <pre class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-3 overflow-x-auto">{{ curlCreateDeck }}</pre>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-sm font-medium">Push cards (batch)</p>
                        <button
                            @click="copy(curlCreateCards, 'create-cards')"
                            class="text-xs px-2 py-1 rounded bg-gray-200 hover:bg-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors"
                        >
                            {{ copiedId === 'create-cards' ? 'Copied!' : 'Copy' }}
                        </button>
                    </div>
                    <pre class="text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg p-3 overflow-x-auto">{{ curlCreateCards }}</pre>
                </div>
            </section>

            <section>
                <h3 class="text-base font-semibold mb-2">Rate limits &amp; errors</h3>
                <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 space-y-1">
                    <li>Authentication failures return <code>401</code>.</li>
                    <li>Accessing another user's deck/card returns <code>403</code>.</li>
                    <li>Validation failures return <code>422</code> with a JSON error body.</li>
                </ul>
            </section>
        </main>
    </div>
</template>
