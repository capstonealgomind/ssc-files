<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    success: Boolean,
    message: String,
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center p-4">
        <Head title="Email Verification" />
        
        <div class="max-w-md w-full">
            <div class="bg-white rounded-lg shadow-xl p-8 text-center">
                <div v-if="success" class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div v-else class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-4">
                    {{ success ? 'Email Verified!' : 'Verification Failed' }}
                </h1>
                
                <p class="text-gray-600 mb-6">{{ message }}</p>

                <div v-if="success" class="bg-blue-50 border border-blue-200 rounded p-4 mb-6 text-left">
                    <p class="text-sm text-blue-800">
                        <strong>✅ What's happening now:</strong>
                    </p>
                    <ul class="mt-2 text-sm text-blue-700 space-y-1">
                        <li>• Your School ID is being processed (OCR)</li>
                        <li>• Verification score is being calculated</li>
                        <li>• Account will be auto-approved if score ≥ 80%</li>
                        <li>• Otherwise, admin will review your registration</li>
                    </ul>
                </div>

                <a href="/login" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Go to Login
                </a>
            </div>
        </div>
    </div>
</template>
