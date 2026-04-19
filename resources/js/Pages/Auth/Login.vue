<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    status: {
        type: String,
    },
});

const page = usePage();

const oauthError = computed(() => page.props.errors?.oauth);
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <div class="space-y-6">
            <div class="text-center">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    Sign in
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Use your Google account to continue.
                </p>
            </div>

            <div
                v-if="status"
                class="text-center text-sm font-medium text-green-600"
            >
                {{ status }}
            </div>

            <div
                v-if="oauthError"
                class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300"
            >
                {{ oauthError }}
            </div>

            <a
                :href="route('auth.redirect', { provider: 'google' })"
                class="flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-900"
            >
                <svg
                    class="h-5 w-5"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 48 48"
                    aria-hidden="true"
                >
                    <path
                        fill="#FFC107"
                        d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.5 29.7 4.5 24 4.5 13.2 4.5 4.5 13.2 4.5 24S13.2 43.5 24 43.5 43.5 34.8 43.5 24c0-1.2-.1-2.4-.3-3.5z"
                    />
                    <path
                        fill="#FF3D00"
                        d="M6.3 14.7l6.6 4.8c1.8-4.3 6-7.3 10.9-7.3 3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.9 6.5 29.7 4.5 24 4.5 16.3 4.5 9.7 8.9 6.3 14.7z"
                    />
                    <path
                        fill="#4CAF50"
                        d="M24 43.5c5.6 0 10.7-2.1 14.6-5.6l-6.7-5.5c-2 1.4-4.6 2.3-7.9 2.3-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39 16.1 43.5 24 43.5z"
                    />
                    <path
                        fill="#1976D2"
                        d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.3 5.8l6.7 5.5c-.5.5 7.3-5.3 7.3-15.3 0-1.2-.1-2.4-.3-3.5z"
                    />
                </svg>
                <span>Continue with Google</span>
            </a>

            <div class="text-center">
                <Link
                    href="/"
                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    &larr; Back to home
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
