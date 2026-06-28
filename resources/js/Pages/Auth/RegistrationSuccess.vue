<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
    voterIdNumber: String,
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center p-4">
        <Head title="Registration Successful" />
        
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Registration Submitted!</h1>
                    <p class="text-gray-600">Your voter registration has been successfully submitted.</p>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Important: Save Your Voter ID</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Your Voter Registration ID:</p>
                                <div class="mt-2 text-3xl font-bold text-blue-700 select-all">
                                    {{ voterIdNumber }}
                                </div>
                                <p class="mt-2 text-red-600 font-semibold">⚠️ Please copy and save this ID for your records!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-6 mb-6">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">📧 Check Your Email</h3>
                    <p class="text-sm text-blue-700">
                        We've sent a verification email with an OTP code and a verification link. 
                        Please verify your email to continue the registration process.
                    </p>
                </div>

                <div class="space-y-4 mb-6">
                    <h3 class="font-semibold text-gray-900">Next Steps:</h3>
                    <ol class="list-decimal list-inside space-y-2 text-gray-700">
                        <li>Check your email inbox for the verification message</li>
                        <li>Click the verification link or use the OTP code</li>
                        <li>Your School ID will be automatically processed</li>
                        <li>Wait for account approval (auto-approved if verification score ≥ 80%)</li>
                        <li>Login to your account once approved</li>
                    </ol>
                </div>

                <div class="bg-red-50 border border-red-200 rounded p-4 mb-6">
                    <p class="text-sm text-red-800">
                        <strong>⚠️ Important:</strong> You cannot login until your email is verified and your account is approved by admin or auto-verification.
                    </p>
                </div>

                <div class="text-center">
                    <div class="space-y-3">
                        <a href="/check-status" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Check My Status
                        </a>
                        <br>
                        <a href="/" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            Return to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
