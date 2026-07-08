<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import GuestHeaderBrand from "@/Components/GuestHeaderBrand.vue";
import RegistrationCountdown from "@/Components/RegistrationCountdown.vue";
import SscMembersCarousel from "@/Components/SscMembersCarousel.vue";
import { useRegistrationWindow } from "@/composables/useRegistrationWindow";

defineProps({
    sscMembers: {
        type: Array,
        default: () => [],
    },
});

const { isRegistrationOpen } = useRegistrationWindow();

const mobileMenuOpen = ref(false);

const pillars = [
    {
        title: "Unity",
        subtitle: "Stronger Together",
        icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z",
    },
    {
        title: "Integrity",
        subtitle: "Leadership with Honor",
        icon: "M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z",
    },
    {
        title: "Service",
        subtitle: "Commitment to All",
        icon: "M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z",
    },
    {
        title: "Excellence",
        subtitle: "Strive. Achieve. Inspire.",
        icon: "M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z",
    },
];

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <Head title="Welcome">
        <link
            v-for="image in sscMembers"
            :key="`preload-ssc-${image.id}`"
            rel="preload"
            as="image"
            :href="image.image_url"
        />
    </Head>

    <div class="guest-shell">
        <header class="guest-header relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    class="min-h-16 flex items-center justify-between gap-3 py-1.5"
                >
                    <GuestHeaderBrand @click="closeMobileMenu" />

                    <nav class="hidden md:flex items-center gap-2">
                        <Link href="/check-status"
                            ><Button variant="ghost" size="sm"
                                >Check Status</Button
                            ></Link
                        >
                        <Link href="/login"
                            ><Button variant="ghost" size="sm"
                                >Log in</Button
                            ></Link
                        >
                        <Link v-if="isRegistrationOpen" href="/register"
                            ><Button variant="navy" size="sm">Register</Button></Link
                        >
                        <Button v-else variant="navy" size="sm" disabled
                            >Register</Button
                        >
                    </nav>

                    <button
                        type="button"
                        class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-md border border-[var(--sscevs-border)] text-[var(--sscevs-black)] transition-colors hover:bg-[var(--sscevs-blue-light)]"
                        :aria-expanded="mobileMenuOpen"
                        aria-label="Toggle menu"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <svg
                            v-if="!mobileMenuOpen"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                v-show="mobileMenuOpen"
                class="md:hidden fixed inset-0 top-16 bg-black/20"
                @click="closeMobileMenu"
            />

            <div
                v-show="mobileMenuOpen"
                class="md:hidden absolute left-0 right-0 top-full border-b border-[var(--sscevs-gold)] shadow-lg bg-white"
            >
                <div
                    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-4 pt-3 space-y-2"
                >
                    <Link
                        href="/check-status"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >Check Status</Button
                        ></Link
                    >
                    <Link href="/login" class="block" @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >Log in</Button
                        ></Link
                    >
                    <Link
                        v-if="isRegistrationOpen"
                        href="/register"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button variant="navy" size="sm" class="w-full"
                            >Register</Button
                        ></Link
                    >
                    <Button
                        v-else
                        variant="navy"
                        size="sm"
                        class="w-full"
                        disabled
                        >Register</Button
                    >
                </div>
            </div>
        </header>

        <section class="guest-hero">
            <div class="guest-hero-inner">
                <div class="guest-hero-stage">
                    <div class="guest-hero-dots" aria-hidden="true">
                        <span v-for="n in 32" :key="n" />
                    </div>

                    <div class="guest-hero-panel">
                        <div class="guest-hero-content">
                            <p class="guest-hero-eyebrow">
                                Supreme Student Council &bull; SSCEVS
                            </p>

                            <h1 class="guest-hero-heading">
                                <span>One Vote.</span>
                                <span>One Voice.</span>
                                <span class="guest-hero-heading-accent"
                                    >One council</span
                                >
                            </h1>

                            <div
                                class="guest-hero-heading-rule"
                                aria-hidden="true"
                            />

                            <p class="guest-hero-lead">
                                The official electronic voting platform for
                                Supreme Student Council elections — secure,
                                transparent, and built for every student voice.
                            </p>
                            <p class="guest-hero-tagline">
                                Together, we lead today for a better tomorrow.
                            </p>

                            <div class="guest-hero-actions">
                                <Link
                                    v-if="isRegistrationOpen"
                                    href="/register"
                                    class="guest-hero-btn guest-hero-btn-primary"
                                >
                                    Get Started
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </Link>
                                <span
                                    v-else
                                    class="guest-hero-btn guest-hero-btn-primary guest-hero-btn-disabled"
                                    aria-disabled="true"
                                >
                                    Get Started
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </span>
                                <Link
                                    href="/login"
                                    class="guest-hero-btn guest-hero-btn-outline"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    Log in
                                </Link>
                                <a
                                    href="#features"
                                    class="guest-hero-btn guest-hero-btn-outline"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        />
                                    </svg>
                                    Learn more
                                </a>
                            </div>

                            <RegistrationCountdown variant="hero" align="start" />
                        </div>
                    </div>

                    <div class="guest-hero-emblem">
                        <img
                            src="/images/hero-image/ssc.png"
                            alt="Supreme Student Council emblem"
                            class="guest-hero-emblem-image"
                        />
                    </div>
                </div>

                <div class="guest-hero-values-wrap">
                    <div class="guest-hero-values">
                        <div
                            v-for="(pillar, index) in pillars"
                            :key="pillar.title"
                            class="guest-hero-pillar"
                            :class="{ 'guest-hero-pillar-divider': index > 0 }"
                        >
                            <div class="guest-hero-pillar-icon-wrap">
                                <svg
                                    class="guest-hero-pillar-icon"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        :d="pillar.icon"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="guest-hero-pillar-title">
                                    {{ pillar.title }}
                                </p>
                                <p class="guest-hero-pillar-sub">
                                    {{ pillar.subtitle }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <SscMembersCarousel :images="sscMembers" />

        <section
            id="features"
            class="guest-features-section py-12 sm:py-16 lg:py-20 px-4 sm:px-6 bg-white"
        >
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-8 sm:mb-12 px-2">
                    <h2
                        class="text-xl sm:text-2xl md:text-3xl font-bold mb-2 sm:mb-3 guest-title"
                    >
                        Everything you need for secure elections
                    </h2>
                    <p
                        class="text-sm sm:text-base max-w-lg mx-auto guest-muted"
                    >
                        Built with transparency, security, and simplicity in
                        mind.
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6"
                >
                    <div
                        v-for="feature in [
                            {
                                title: 'Secure Voting',
                                desc: 'End-to-end encrypted votes ensure your selection is private and tamper-proof.',
                                icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                            },
                            {
                                title: 'Real-time Results',
                                desc: 'Live vote tallying with instant results published as soon as polls close.',
                                icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                            },
                            {
                                title: 'Role Management',
                                desc: 'Separate admin and voter roles with tailored permissions and dashboards.',
                                icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                            },
                            {
                                title: 'Verified Voters',
                                desc: 'Only verified students can participate, keeping your elections fair and legitimate.',
                                icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                            },
                            {
                                title: 'Mobile Friendly',
                                desc: 'Vote from any device — desktop, tablet, or phone. Fully responsive design.',
                                icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                            },
                            {
                                title: 'Instant Access',
                                desc: 'Register in minutes and get immediate access to active elections and results.',
                                icon: 'M13 10V3L4 14h7v7l9-11h-7z',
                            },
                        ]"
                        :key="feature.title"
                        class="guest-card p-5 sm:p-6"
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-3 sm:mb-4 guest-feature-icon"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="feature.icon"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-1.5 sm:mb-2 text-sm sm:text-base guest-title"
                        >
                            {{ feature.title }}
                        </h3>
                        <p
                            class="text-xs sm:text-sm leading-relaxed guest-muted"
                        >
                            {{ feature.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="py-12 sm:py-16 lg:py-20 px-4 sm:px-6 text-center bg-white"
        >
            <div class="max-w-xl mx-auto">
                <h2
                    class="text-xl sm:text-2xl md:text-3xl font-bold mb-3 sm:mb-4 px-2 guest-title"
                >
                    Ready to participate?
                </h2>
                <p class="text-sm sm:text-base mb-6 sm:mb-8 px-2 guest-muted">
                    Join thousands of students who use SSCEVS for transparent
                    and secure school elections.
                </p>
                <div
                    class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-center max-w-xs sm:max-w-none mx-auto"
                >
                    <Link
                        v-if="isRegistrationOpen"
                        href="/register"
                        class="w-full sm:w-auto"
                        ><Button size="lg" class="w-full sm:min-w-36"
                            >Create an account</Button
                        ></Link
                    >
                    <Button
                        v-else
                        size="lg"
                        class="w-full sm:min-w-36"
                        disabled
                        >Create an account</Button
                    >
                    <Link href="/login" class="w-full sm:w-auto"
                        ><Button
                            variant="outline"
                            size="lg"
                            class="w-full sm:min-w-36"
                            >Sign in</Button
                        ></Link
                    >
                </div>
            </div>
        </section>

        <footer class="guest-footer py-5 sm:py-6 px-4 text-center bg-white">
            <p class="text-xs leading-relaxed">
                &copy; {{ new Date().getFullYear() }} SSCEVS. All rights
                reserved.
            </p>
        </footer>
    </div>
</template>
