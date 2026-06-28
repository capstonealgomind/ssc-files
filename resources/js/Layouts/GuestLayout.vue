<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col" style="background-color: hsl(240 4.8% 95.9%);">
        <!-- Top nav bar -->
        <header class="w-full border-b" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-md flex items-center justify-center" style="background-color: hsl(240 5.9% 10%);">
                        <svg class="h-4 w-4" style="color: hsl(0 0% 98%);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="font-semibold text-sm" style="color: hsl(240 10% 3.9%);">SSCEVS</span>
                </Link>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 flex items-center justify-center p-4 py-12">
            <slot />
        </main>

    </div>
</template>
