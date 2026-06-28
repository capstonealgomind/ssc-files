<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: Object,
        default: null,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    voter_id: '',
});

const submit = () => {
    form.post('/check-status', {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Form submitted successfully');
        },
        onError: (errors) => {
            console.log('Form errors:', errors);
        },
    });
};

const getStatusIcon = (status) => {
    if (status === 'verified' || status === 'completed' || status === 'approved') return '✅';
    if (status === 'processing') return '🟡';
    if (status === 'failed' || status === 'rejected') return '❌';
    return '🟡';
};

const getStatusText = (status) => {
    if (status === 'verified') return 'Verified';
    if (status === 'pending') return 'Pending';
    if (status === 'processing') return 'Processing';
    if (status === 'completed') return 'Completed';
    if (status === 'failed') return 'Failed';
    if (status === 'approved') return 'Approved';
    if (status === 'rejected') return 'Rejected';
    return status;
};

const getStatusColor = (status) => {
    if (status === 'verified' || status === 'completed' || status === 'approved') return 'text-green-700 bg-green-50 border-green-200';
    if (status === 'processing') return 'text-yellow-700 bg-yellow-50 border-yellow-200';
    if (status === 'failed' || status === 'rejected') return 'text-red-700 bg-red-50 border-red-200';
    return 'text-gray-700 bg-gray-50 border-gray-200';
};

const reset = () => {
    form.reset();
    form.clearErrors();
    window.location.href = '/check-status';
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100">
        <Head title="Check Registration Status" />

        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">SSCEVS - Check Status</h1>
                    <div class="space-x-4">
                        <a href="/" class="text-blue-600 hover:text-blue-800">Home</a>
                        <a href="/login" class="text-blue-600 hover:text-blue-800">Login</a>
                        <a href="/register" class="text-blue-600 hover:text-blue-800">Register</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto">
                <!-- Search Form -->
                <div class="bg-white rounded-lg shadow-xl p-8 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Check Your Registration Status</h2>
                    <p class="text-gray-600 mb-6">Enter your Voter ID to check your registration and verification status.</p>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="voter_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Voter ID Number
                            </label>
                            <input
                                id="voter_id"
                                v-model="form.voter_id"
                                type="text"
                                placeholder="e.g., VID-2026-00001"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.voter_id }"
                            />
                            <p v-if="form.errors.voter_id || errors.voter_id" class="mt-2 text-sm text-red-600">
                                {{ form.errors.voter_id || errors.voter_id }}
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 font-semibold"
                            >
                                {{ form.processing ? 'Checking...' : 'Check Status' }}
                            </button>
                            <button
                                v-if="status"
                                type="button"
                                @click="reset"
                                class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                            >
                                Clear
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Status Results -->
                <div v-if="status" class="bg-white rounded-lg shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Registration Status</h2>

                    <!-- Voter Info -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-blue-800 font-semibold">Name:</p>
                                <p class="text-lg font-bold text-blue-900">{{ status.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-blue-800 font-semibold">Voter ID:</p>
                                <p class="text-lg font-bold text-blue-900">{{ status.voterIdNumber }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Status Cards -->
                    <div class="space-y-4 mb-6">
                        <!-- Email Status -->
                        <div :class="['border-2 rounded-lg p-5', getStatusColor(status.emailStatus)]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold mb-1">Email:</h3>
                                    <p class="text-2xl">
                                        {{ getStatusIcon(status.emailStatus) }} {{ getStatusText(status.emailStatus) }}
                                    </p>
                                </div>
                            </div>
                            <p v-if="status.emailStatus === 'pending'" class="text-sm mt-3">
                                Please check your email inbox and click the verification link to verify your email address.
                            </p>
                        </div>

                        <!-- School ID Status -->
                        <div :class="['border-2 rounded-lg p-5', getStatusColor(status.ocrStatus)]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold mb-1">School ID:</h3>
                                    <p class="text-2xl">
                                        {{ getStatusIcon(status.ocrStatus) }} {{ getStatusText(status.ocrStatus) }}
                                    </p>
                                </div>
                            </div>
                            <p v-if="status.ocrStatus === 'pending'" class="text-sm mt-3">
                                Your School ID will be processed after you verify your email.
                            </p>
                            <p v-else-if="status.ocrStatus === 'processing'" class="text-sm mt-3">
                                Your School ID image is being processed. This may take a few moments. Please check back later.
                            </p>
                            <p v-else-if="status.ocrStatus === 'completed'" class="text-sm mt-3">
                                Your School ID has been successfully processed.
                            </p>
                            <p v-else-if="status.ocrStatus === 'failed'" class="text-sm mt-3">
                                There was an issue processing your School ID. Please contact support.
                            </p>
                        </div>

                        <!-- Account Status -->
                        <div :class="['border-2 rounded-lg p-5', getStatusColor(status.verificationStatus)]">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold mb-1">Account:</h3>
                                    <p class="text-2xl">
                                        {{ getStatusIcon(status.verificationStatus) }} {{ getStatusText(status.verificationStatus) }}
                                    </p>
                                </div>
                            </div>
                            <p v-if="status.verificationStatus === 'pending' && status.emailStatus === 'verified'" class="text-sm mt-3">
                                Your account is under review. This process may take some time.
                            </p>
                            <p v-else-if="status.verificationStatus === 'pending' && status.emailStatus === 'pending'" class="text-sm mt-3">
                                Please verify your email first to continue the verification process.
                            </p>
                            <p v-else-if="status.verificationStatus === 'approved'" class="text-sm mt-3">
                                Your account has been approved! You can now login to vote.
                            </p>
                            <p v-else-if="status.verificationStatus === 'rejected'" class="text-sm mt-3">
                                Your account was rejected. Please contact support for more information.
                            </p>
                        </div>
                    </div>

                    <!-- Verification Score -->
                    <div v-if="status.fraudScore !== null && status.fraudScore !== undefined" class="bg-gray-50 border-2 border-gray-200 rounded-lg p-5 mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">Verification Score:</h3>
                        <div class="flex items-center">
                            <div class="flex-1 bg-gray-200 rounded-full h-6">
                                <div 
                                    class="h-6 rounded-full transition-all flex items-center justify-center text-white text-sm font-bold"
                                    :class="status.fraudScore >= 80 ? 'bg-green-500' : status.fraudScore >= 50 ? 'bg-yellow-500' : 'bg-red-500'"
                                    :style="{ width: `${Math.max(Math.min(status.fraudScore, 100), 10)}%` }"
                                >
                                    {{ status.fraudScore }}
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mt-3">
                            {{ status.fraudScore >= 80 ? '✅ High confidence - Auto-approved' : status.fraudScore >= 50 ? '⚠️ Moderate - Under admin review' : '❌ Low - Manual review required' }}
                        </p>
                    </div>

                    <!-- Overall Status -->
                    <div v-if="status.isVerified" class="bg-green-50 border-2 border-green-500 rounded-lg p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-green-800">✅ Account Fully Verified!</h3>
                                <p class="mt-2 text-sm text-green-700">
                                    Your account has been fully verified and approved. You can now login and participate in elections.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-5">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-bold text-yellow-800">⚠️ Verification In Progress</h3>
                                <p class="mt-2 text-sm text-yellow-700">
                                    Your account is not yet fully verified. Please complete all verification steps above. You will not be able to login until your account is approved.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
