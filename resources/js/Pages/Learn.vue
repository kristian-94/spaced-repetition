<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const isAuthed = !!page.props.auth?.user;

// Dark mode: 'system' | 'light' | 'dark' (shares localStorage key with AppLayout)
const themeMode = ref('system');

onMounted(() => {
    themeMode.value = localStorage.getItem('themeMode') || 'system';
    applyTheme();
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (themeMode.value === 'system') applyTheme();
    });
});

function cycleTheme() {
    const modes = ['system', 'light', 'dark'];
    themeMode.value = modes[(modes.indexOf(themeMode.value) + 1) % modes.length];
    localStorage.setItem('themeMode', themeMode.value);
    applyTheme();
}

function applyTheme() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = themeMode.value === 'dark' || (themeMode.value === 'system' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark);
}

const ratings = [
    {
        label: 'Again',
        meaning: 'You forgot it.',
        effect: 'Card resets. You\'ll see it again within minutes.',
        tone: 'text-rose-600 dark:text-rose-400',
    },
    {
        label: 'Hard',
        meaning: 'You remembered, but it was a struggle.',
        effect: 'Interval grows slowly; you\'ll see it again soon.',
        tone: 'text-amber-600 dark:text-amber-400',
    },
    {
        label: 'Good',
        meaning: 'You remembered with a little effort.',
        effect: 'Normal interval growth.',
        tone: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        label: 'Easy',
        meaning: 'You recalled it immediately, with no effort.',
        effect: 'Interval jumps ahead. You won\'t see this card for a while.',
        tone: 'text-indigo-600 dark:text-indigo-400',
    },
];

const progression = [
    { when: 'Day 0', what: 'You learn a new card.' },
    { when: '10 minutes later', what: 'A short check to confirm the card landed.' },
    { when: '1 day later', what: 'First real review.' },
    { when: '6 days later', what: 'If you still remember, the interval doubles.' },
    { when: '~2 weeks later', what: 'If you still remember, it doubles again.' },
    { when: 'A month later', what: 'The card is settling into long-term memory.' },
    { when: 'Months later', what: 'A card you once crammed is now something you reliably know.' },
];
</script>

<template>
    <Head title="What is spaced repetition?" />

    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900 dark:from-gray-950 dark:to-gray-900 dark:text-gray-100">
        <!-- Nav -->
        <header class="border-b border-gray-200/60 dark:border-gray-800/60">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <Link :href="route('home')" class="flex items-center gap-2 text-lg font-semibold">
                    <span class="select-none" aria-hidden="true">🧠</span>
                    <span>Spaced Repetition</span>
                </Link>
                <nav class="flex items-center gap-2 text-sm">
                    <Link
                        :href="route('docs.api')"
                        class="hidden rounded-md px-3 py-2 text-gray-600 transition hover:text-gray-900 sm:inline-block dark:text-gray-400 dark:hover:text-gray-100"
                    >
                        API
                    </Link>
                    <button
                        type="button"
                        @click="cycleTheme"
                        :aria-label="`Theme: ${themeMode}. Click to change.`"
                        :title="`Theme: ${themeMode === 'system' ? 'System' : themeMode === 'light' ? 'Light' : 'Dark'}`"
                        class="rounded-md p-2 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                    >
                        <!-- System -->
                        <svg v-if="themeMode === 'system'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <!-- Light -->
                        <svg v-else-if="themeMode === 'light'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <!-- Dark -->
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <Link
                        v-if="isAuthed"
                        :href="route('decks.index')"
                        class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Open app
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Sign in
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-3xl px-6 pb-12 pt-16 sm:pt-20">
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                A quick primer
            </p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">
                What is spaced repetition?
            </h1>
            <p class="mt-6 text-lg text-gray-600 dark:text-gray-400">
                It is a learning technique that shows you information
                <em>just before you are about to forget it</em>. With a
                few well-timed reviews, the memory holds for years
                instead of days.
            </p>
        </section>

        <!-- Forgetting curve -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                The problem: you forget almost everything
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                In the 1880s, a psychologist named Hermann Ebbinghaus ran
                an experiment on himself. He memorised long lists of
                nonsense syllables, then tested how much he could still
                recall as days passed. The result was a steep curve:
            </p>

            <!-- Simple inline SVG forgetting curve -->
            <figure class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <svg viewBox="0 0 400 180" class="h-auto w-full" role="img" aria-label="The forgetting curve drops sharply in the first day then flattens.">
                    <!-- axes -->
                    <line x1="40" y1="20" x2="40" y2="150" stroke="currentColor" stroke-width="1" class="text-gray-300 dark:text-gray-700" />
                    <line x1="40" y1="150" x2="380" y2="150" stroke="currentColor" stroke-width="1" class="text-gray-300 dark:text-gray-700" />

                    <!-- grid labels -->
                    <text x="30" y="28" text-anchor="end" class="fill-gray-500 text-[10px]">100%</text>
                    <text x="30" y="92" text-anchor="end" class="fill-gray-500 text-[10px]">50%</text>
                    <text x="30" y="154" text-anchor="end" class="fill-gray-500 text-[10px]">0%</text>

                    <text x="40" y="170" class="fill-gray-500 text-[10px]">now</text>
                    <text x="200" y="170" text-anchor="middle" class="fill-gray-500 text-[10px]">a few days</text>
                    <text x="380" y="170" text-anchor="end" class="fill-gray-500 text-[10px]">a month</text>

                    <!-- forgetting curve -->
                    <path d="M 40 25 Q 80 100, 150 125 T 380 148" fill="none" stroke="currentColor" stroke-width="2.5" class="text-rose-500" />
                </svg>
                <figcaption class="mt-3 text-sm text-gray-500 dark:text-gray-500">
                    Without review, you lose roughly half of new information
                    within days, and most of it within a month.
                </figcaption>
            </figure>
        </section>

        <!-- The fix -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                The fix: review at the right moment
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Every time you successfully recall something, the memory
                strengthens and the interval before the next review
                grows. This is known as the <strong>spacing effect</strong>.
            </p>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Review too early and you waste time on something you
                already know. Review too late and you have already
                forgotten, so the session becomes relearning rather
                than reinforcement. Spaced repetition aims for the
                moment just before recall would fail.
            </p>

            <figure class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <svg viewBox="0 0 480 240" class="h-auto w-full" role="img" aria-label="Each review resets retention to 100% and the following decay is slower than the one before, so the curve flattens out over time.">
                    <!-- axes -->
                    <line x1="50" y1="20" x2="50" y2="200" stroke="currentColor" stroke-width="1" class="text-gray-300 dark:text-gray-700" />
                    <line x1="50" y1="200" x2="470" y2="200" stroke="currentColor" stroke-width="1" class="text-gray-300 dark:text-gray-700" />

                    <!-- y-axis label -->
                    <text x="18" y="110" text-anchor="middle" transform="rotate(-90, 18, 110)" class="fill-gray-500 text-[10px] font-semibold">Retention</text>
                    <text x="44" y="34" text-anchor="end" class="fill-gray-400 text-[9px]">100%</text>
                    <text x="44" y="204" text-anchor="end" class="fill-gray-400 text-[9px]">0%</text>

                    <!-- x-axis label + Day 0 -->
                    <text x="260" y="232" text-anchor="middle" class="fill-gray-500 text-[10px] font-semibold">Time</text>
                    <line x1="100" y1="30" x2="100" y2="200" stroke-dasharray="3 3" stroke="currentColor" stroke-width="1" class="text-gray-300 dark:text-gray-700" />
                    <text x="100" y="216" text-anchor="middle" class="fill-gray-500 text-[10px]">Day 0</text>

                    <!-- Review arrows -->
                    <g class="text-indigo-500 dark:text-indigo-400">
                        <line x1="150" y1="12" x2="150" y2="28" stroke="currentColor" stroke-width="1.5" />
                        <polygon points="146,24 154,24 150,30" fill="currentColor" />
                        <line x1="230" y1="12" x2="230" y2="28" stroke="currentColor" stroke-width="1.5" />
                        <polygon points="226,24 234,24 230,30" fill="currentColor" />
                        <line x1="340" y1="12" x2="340" y2="28" stroke="currentColor" stroke-width="1.5" />
                        <polygon points="336,24 344,24 340,30" fill="currentColor" />
                    </g>
                    <text x="245" y="9" text-anchor="middle" class="fill-indigo-600 dark:fill-indigo-400 text-[10px] font-semibold">Reviews</text>

                    <!-- Learn point -->
                    <circle cx="100" cy="30" r="3.5" class="fill-rose-500" />
                    <text x="100" y="22" text-anchor="middle" class="fill-gray-700 dark:fill-gray-300 text-[10px] font-semibold">Learn</text>

                    <!-- Dashed "no-review" forgetting curve for reference -->
                    <path d="M 100 30 Q 150 180, 460 195" fill="none" stroke="currentColor" stroke-width="1.75" stroke-dasharray="4 3" class="text-rose-300 dark:text-rose-400/60" />

                    <!-- Main sawtooth: each decay flatter and longer than the last -->
                    <path
                        d="M 100 30
                           Q 122 78, 150 95
                           L 150 32
                           Q 185 60, 230 80
                           L 230 32
                           Q 280 50, 340 70
                           L 340 32
                           Q 395 45, 460 60"
                        fill="none" stroke="currentColor" stroke-width="2.5" class="text-rose-500"
                    />

                    <!-- Callout labels -->
                    <text x="375" y="42" class="fill-gray-600 dark:fill-gray-400 text-[10px] italic">flattens out</text>
                    <text x="175" y="150" class="fill-gray-500 dark:fill-gray-500 text-[10px] italic">without review</text>

                    <!-- "Better long-term retention" bracket on the right -->
                    <g class="text-gray-400 dark:text-gray-500">
                        <path d="M 468 60 L 472 60 L 472 188 L 468 188" fill="none" stroke="currentColor" stroke-width="1" />
                    </g>
                    <text x="462" y="120" text-anchor="end" class="fill-gray-600 dark:fill-gray-400 text-[9px] italic">
                        <tspan x="462" dy="0">better</tspan>
                        <tspan x="462" dy="11">long-term</tspan>
                        <tspan x="462" dy="11">retention</tspan>
                    </text>
                </svg>
                <figcaption class="mt-3 text-sm text-gray-500 dark:text-gray-500">
                    Each review resets retention to 100%, and the next decay is
                    slower than the one before. Intervals grow from days to
                    weeks to months while retention stays high.
                </figcaption>
            </figure>

            <div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50 p-5 text-indigo-900 dark:border-indigo-900/60 dark:bg-indigo-950/40 dark:text-indigo-200">
                <p class="text-sm leading-relaxed">
                    <strong>The goal:</strong> retain a target percentage
                    of your material (say, 90%) while doing the
                    <em>minimum number of reviews needed</em> to get there.
                </p>
            </div>
        </section>

        <!-- How reviews work -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                How a review works
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                You are shown the front of a card and try to recall the
                answer <em>before</em> flipping it. This is called
                <strong>active recall</strong>, and it is the mechanism
                that builds memory. Re-reading notes feels productive but
                does little to strengthen recall; the effort of retrieval
                is what does.
            </p>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                After flipping, you rate how well you did. That rating
                tells the scheduler when to show the card next:
            </p>

            <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-950/40 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Rating</th>
                            <th class="px-5 py-3 font-semibold">What it means</th>
                            <th class="hidden px-5 py-3 font-semibold sm:table-cell">What happens next</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <tr v-for="r in ratings" :key="r.label">
                            <td class="px-5 py-4 align-top font-semibold" :class="r.tone">{{ r.label }}</td>
                            <td class="px-5 py-4 align-top text-gray-700 dark:text-gray-300">
                                {{ r.meaning }}
                                <div class="mt-1 text-gray-500 dark:text-gray-500 sm:hidden">{{ r.effect }}</div>
                            </td>
                            <td class="hidden px-5 py-4 align-top text-gray-600 dark:text-gray-400 sm:table-cell">{{ r.effect }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Progression -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                What a card's life looks like
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                A card you keep getting right spaces out quickly. After
                four or five successful reviews, you will only see it
                once every few months, because you already know it.
            </p>

            <ol class="mt-6 space-y-3">
                <li
                    v-for="(step, i) in progression"
                    :key="step.when"
                    class="flex items-start gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex h-7 w-7 flex-none items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {{ i + 1 }}
                    </div>
                    <div>
                        <div class="text-sm font-semibold">{{ step.when }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ step.what }}</div>
                    </div>
                </li>
            </ol>
        </section>

        <!-- Why not cramming -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                Why this beats cramming
            </h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-sm font-semibold text-rose-600 dark:text-rose-400">Cramming</div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Hours in one sitting. You pass the test on Monday,
                        and by Friday most of the material is lost to the
                        forgetting curve.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Spaced repetition</div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        A few minutes a day. Each review reinforces the
                        memory and pushes the next one further out. After
                        a few weeks the review load is small, and the
                        material stays with you.
                    </p>
                </div>
            </div>
        </section>

        <!-- What makes a good card -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                What makes a good card
            </h2>
            <ul class="mt-6 space-y-3 text-gray-700 dark:text-gray-300">
                <li class="flex gap-3">
                    <span class="mt-1 text-indigo-500" aria-hidden="true">✓</span>
                    <span><strong>One fact per card.</strong> Don't stuff multiple concepts together. If you can't recall half of it, the whole card fails.</span>
                </li>
                <li class="flex gap-3">
                    <span class="mt-1 text-indigo-500" aria-hidden="true">✓</span>
                    <span><strong>Question on the front, answer on the back.</strong> Force yourself to retrieve, not recognise.</span>
                </li>
                <li class="flex gap-3">
                    <span class="mt-1 text-indigo-500" aria-hidden="true">✓</span>
                    <span><strong>Be specific.</strong> Vague questions produce inconsistent ratings and noisy scheduling.</span>
                </li>
                <li class="flex gap-3">
                    <span class="mt-1 text-indigo-500" aria-hidden="true">✓</span>
                    <span><strong>Keep it short.</strong> If you need a paragraph, split it into several cards.</span>
                </li>
            </ul>
        </section>

        <!-- The algorithm -->
        <section class="mx-auto max-w-3xl px-6 py-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                A word on the algorithm
            </h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                This app uses <strong>FSRS</strong>, the Free Spaced
                Repetition Scheduler. It is a modern algorithm that
                models three properties per card: how <em>stable</em>
                the memory is, how <em>difficult</em> the card is for
                you specifically, and how <em>retrievable</em> it is
                right now.
            </p>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Compared to the classic SM-2 algorithm (the scheduler
                Anki used for years), FSRS typically needs 20–30% fewer
                reviews to reach the same retention, because it fits
                the forgetting curve to <em>your</em> review history
                rather than applying a fixed multiplier to every card.
            </p>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                You do not need to think about any of this to use the
                app. Rate your cards honestly, and the scheduler handles
                the rest.
            </p>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-3xl px-6 py-16 text-center">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                Try it on your own material
            </h2>
            <p class="mt-3 text-gray-600 dark:text-gray-400">
                Create a deck and review it for a few minutes each day.
                The scheduler takes care of the rest.
            </p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Link
                    v-if="isAuthed"
                    :href="route('decks.index')"
                    class="inline-flex items-center justify-center rounded-md bg-gray-900 px-6 py-3 text-base font-medium text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                >
                    Open my decks &rarr;
                </Link>
                <a
                    v-else
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
                <Link
                    :href="route('home')"
                    class="inline-flex items-center justify-center rounded-md px-6 py-3 text-base font-medium text-gray-700 transition hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100"
                >
                    Back to home
                </Link>
            </div>
        </section>

        <footer class="border-t border-gray-200/60 dark:border-gray-800/60">
            <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-2 px-6 py-6 text-sm text-gray-500 sm:flex-row dark:text-gray-500">
                <div>🧠 Spaced Repetition</div>
                <div class="flex gap-4">
                    <Link :href="route('home')" class="hover:text-gray-900 dark:hover:text-gray-100">Home</Link>
                    <Link :href="route('docs.api')" class="hover:text-gray-900 dark:hover:text-gray-100">API docs</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
