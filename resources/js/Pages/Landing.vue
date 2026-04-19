<script setup>
import { Head, Link } from '@inertiajs/vue3';

const features = [
    {
        title: 'FSRS-powered scheduling',
        body: 'Cards resurface at the precise moment you\'re about to forget them, using the modern FSRS algorithm. Less time reviewing, more time remembering.',
        icon: '🧠',
    },
    {
        title: 'One queue for everything',
        body: 'Review all your decks in a single session, or drill a specific deck. A daily boost keeps your streak alive on busy days.',
        icon: '⚡',
    },
    {
        title: 'Friendly to AI agents',
        body: 'Generate a token and let Claude or ChatGPT push flashcards into your decks via a simple REST API. Learning at the speed of a prompt.',
        icon: '🤖',
    },
    {
        title: 'Reminders that nudge, not nag',
        body: 'Optional Telegram notifications when reviews are due. No app to install, no push permissions to juggle.',
        icon: '🔔',
    },
    {
        title: 'Sign in with Google',
        body: 'No passwords to forget. One click to get started, one click to come back.',
        icon: '🔐',
    },
    {
        title: 'Yours to own',
        body: 'Self-hosted and open. Your decks, your data, your pace.',
        icon: '📚',
    },
];
</script>

<template>
    <Head title="Spaced Repetition — remember what matters" />

    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100">
        <!-- Nav -->
        <header class="border-b border-gray-200/60 dark:border-gray-800/60">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2 text-lg font-semibold">
                    <span class="select-none" aria-hidden="true">🧠</span>
                    <span>Spaced Repetition</span>
                </div>
                <nav class="flex items-center gap-2 text-sm">
                    <Link
                        :href="route('docs.api')"
                        class="hidden rounded-md px-3 py-2 text-gray-600 transition hover:text-gray-900 sm:inline-block dark:text-gray-400 dark:hover:text-gray-100"
                    >
                        API
                    </Link>
                    <Link
                        :href="route('login')"
                        class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Sign in
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-5xl px-6 pb-16 pt-16 sm:pt-24">
            <div class="text-center">
                <div class="mb-6 text-6xl" aria-hidden="true">🧠</div>
                <h1 class="mx-auto max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
                    Remember what you learn.
                    <span class="block text-indigo-600 dark:text-indigo-400">For good this time.</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                    A tiny, focused spaced-repetition app. Make flashcards,
                    review a few minutes a day, and actually keep what you
                    study — whether it's a language, anatomy, or the capitals
                    of the world.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a
                        :href="route('auth.redirect', { provider: 'google' })"
                        class="inline-flex items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 48 48"
                            aria-hidden="true"
                        >
                            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.5 29.7 4.5 24 4.5 13.2 4.5 4.5 13.2 4.5 24S13.2 43.5 24 43.5 43.5 34.8 43.5 24c0-1.2-.1-2.4-.3-3.5z" />
                            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8c1.8-4.3 6-7.3 10.9-7.3 3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.5 29.7 4.5 24 4.5 16.3 4.5 9.7 8.9 6.3 14.7z" />
                            <path fill="#4CAF50" d="M24 43.5c5.6 0 10.7-2.1 14.6-5.6l-6.7-5.5c-2 1.4-4.6 2.3-7.9 2.3-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39 16.1 43.5 24 43.5z" />
                            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.3 5.8l6.7 5.5c-.5.5 7.3-5.3 7.3-15.3 0-1.2-.1-2.4-.3-3.5z" />
                        </svg>
                        <span>Get started with Google</span>
                    </a>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center justify-center rounded-md px-6 py-3 text-base font-medium text-gray-700 transition hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100"
                    >
                        I already have an account &rarr;
                    </Link>
                </div>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-500">
                    Free. No credit card. One-click sign in.
                </p>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto max-w-5xl px-6 pb-20">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="mb-3 text-2xl" aria-hidden="true">{{ feature.icon }}</div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ feature.title }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        {{ feature.body }}
                    </p>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="border-t border-gray-200/60 bg-gray-50 dark:border-gray-800/60 dark:bg-gray-950/60">
            <div class="mx-auto max-w-5xl px-6 py-16">
                <h2 class="text-center text-2xl font-bold tracking-tight sm:text-3xl">
                    How it works
                </h2>
                <ol class="mx-auto mt-10 grid max-w-3xl gap-6 sm:grid-cols-3">
                    <li class="relative rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                        <div class="mb-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400">Step 1</div>
                        <div class="font-medium">Make a deck</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Type cards by hand, or let an AI agent generate them via the API.
                        </p>
                    </li>
                    <li class="relative rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                        <div class="mb-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400">Step 2</div>
                        <div class="font-medium">Review daily</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Spend five minutes a day rating how well you recalled each card.
                        </p>
                    </li>
                    <li class="relative rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                        <div class="mb-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400">Step 3</div>
                        <div class="font-medium">Remember it</div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            FSRS spaces reviews so knowledge actually sticks, long-term.
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Bottom CTA -->
        <section class="mx-auto max-w-3xl px-6 py-20 text-center">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                Start remembering.
            </h2>
            <p class="mt-3 text-gray-600 dark:text-gray-400">
                Sign in and create your first deck in under a minute.
            </p>
            <div class="mt-8">
                <a
                    :href="route('auth.redirect', { provider: 'google' })"
                    class="inline-flex items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.5 29.7 4.5 24 4.5 13.2 4.5 4.5 13.2 4.5 24S13.2 43.5 24 43.5 43.5 34.8 43.5 24c0-1.2-.1-2.4-.3-3.5z" />
                        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8c1.8-4.3 6-7.3 10.9-7.3 3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.5 29.7 4.5 24 4.5 16.3 4.5 9.7 8.9 6.3 14.7z" />
                        <path fill="#4CAF50" d="M24 43.5c5.6 0 10.7-2.1 14.6-5.6l-6.7-5.5c-2 1.4-4.6 2.3-7.9 2.3-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39 16.1 43.5 24 43.5z" />
                        <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.3 5.8l6.7 5.5c-.5.5 7.3-5.3 7.3-15.3 0-1.2-.1-2.4-.3-3.5z" />
                    </svg>
                    <span>Get started with Google</span>
                </a>
            </div>
        </section>

        <footer class="border-t border-gray-200/60 dark:border-gray-800/60">
            <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-2 px-6 py-6 text-sm text-gray-500 sm:flex-row dark:text-gray-500">
                <div>🧠 Spaced Repetition</div>
                <div class="flex gap-4">
                    <Link :href="route('docs.api')" class="hover:text-gray-900 dark:hover:text-gray-100">API docs</Link>
                    <Link :href="route('login')" class="hover:text-gray-900 dark:hover:text-gray-100">Sign in</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
