<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    emailStatus: String,
    ocrStatus: String,
    verificationStatus: String,
    isVerified: Boolean,
    fraudScore: Number,
    voterIdNumber: String,
});

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
</script>

<template>
    <AppLayout>
        <Head title="Registration Status" />

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Registration Status</h2>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p class="text-sm text-blue-800 font-semibold">Your Voter ID:</p>
                            <p class="text-2xl font-bold text-blue-700">{{ voterIdNumber }}</p>
                        </div>

                        <div class="space-y-4">
                            <div :class="['border rounded-lg p-4', getStatusColor(emailStatus)]">
                                <h3 class="font-semibold">Email:</h3>
                                <p class="text-sm">{{ getStatusIcon(emailStatus) }} {{ getStatusText(emailStatus) }}</p>
                            </div>

                            <div :class="['border rounded-lg p-4', getStatusColor(ocrStatus)]">
                                <h3 class="font-semibold">School ID:</h3>
                                <p class="text-sm">{{ getStatusIcon(ocrStatus) }} {{ getStatusText(ocrStatus) }}</p>
                                <p v-if="ocrStatus === 'processing'" class="text-xs mt-2">
                                    Your School ID image is being processed. This may take a few moments.
                                </p>
                            </div>

                            <div :class="['border rounded-lg p-4', getStatusColor(verificationStatus)]">
                                <h3 class="font-semibold">Account:</h3>
                                <p class="text-sm">{{ getStatusIcon(verificationStatus) }} {{ getStatusText(verificationStatus) }}</p>
                                <p v-if="verificationStatus === 'pending' && emailStatus === 'verified'" class="text-xs mt-2">
                                    Your registration is under review.
                                </p>
                            </div>

                            <div v-if="fraudScore !== null && fraudScore !== undefined" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900">Verification Score:</h3>
                                <div class="flex items-center mt-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-4">
                                        <div 
                                            class="h-4 rounded-full transition-all"
                                            :class="fraudScore >= 80 ? 'bg-green-500' : fraudScore >= 50 ? 'bg-yellow-500' : 'bg-red-500'"
                                            :style="{ width: `${Math.min(fraudScore, 100)}%` }"
                                        ></div>
                                    </div>
                                    <span class="ml-3 font-bold text-gray-900">{{ fraudScore }}</span>
                                </div>
                                <p class="text-xs text-gray-600 mt-2">
                                    {{ fraudScore >= 80 ? '✅ High confidence' : fraudScore >= 50 ? '⚠️ Under review' : '❌ Manual review required' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="!isVerified" class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800">
                                <strong>⚠️ Note:</strong> Your account is not yet verified. You may have limited access.
                            </p>
                        </div>

                        <div v-else class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-sm text-green-800">
                                <strong>✅ Verified!</strong> Your account has been verified.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
