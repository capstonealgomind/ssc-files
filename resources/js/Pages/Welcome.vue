<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import CircularGallery from "@/Components/CircularGallery/CircularGallery.vue";
import DomeGallery from "@/Components/DomeGallery/DomeGallery.vue";
import PixelTransition from "@/Components/PixelTransition/PixelTransition.vue";
import GuestHeaderBrand from "@/Components/GuestHeaderBrand.vue";
import RegistrationCountdown from "@/Components/RegistrationCountdown.vue";
import SscMembersCarousel from "@/Components/SscMembersCarousel.vue";
import AivaFloatingAssistant from "@/Components/AivaFloatingAssistant.vue";
import { useRegistrationWindow } from "@/composables/useRegistrationWindow";

const props = defineProps({
    sscMembers: {
        type: Array,
        default: () => [],
    },
    galleryImages: {
        type: Array,
        default: () => [],
    },
    galleryStyle: {
        type: String,
        default: "dome",
    },
});

const domeGalleryImages = computed(() =>
    props.galleryImages.map((image) => ({
        src: image.image_url,
        alt: "SSCEVS gallery",
    })),
);

const circularGalleryItems = computed(() =>
    props.galleryImages.map((image) => ({
        image: image.image_url,
        text: "",
    })),
);

const isCircularGallery = computed(
    () => props.galleryStyle === "circular",
);

const galleryViewport = ref("desktop");

const circularBend = computed(() => {
    if (galleryViewport.value === "mobile") {
        return 1.2;
    }

    if (galleryViewport.value === "tablet") {
        return 2;
    }

    return 3;
});

const circularFont = computed(() => {
    if (galleryViewport.value === "mobile") {
        return "bold 18px Figtree";
    }

    if (galleryViewport.value === "tablet") {
        return "bold 22px Figtree";
    }

    return "bold 28px Figtree";
});

const circularScrollSpeed = computed(() =>
    galleryViewport.value === "mobile" ? 1.4 : 2,
);

function syncGalleryViewport() {
    if (typeof window === "undefined") {
        return;
    }

    if (window.matchMedia("(max-width: 640px)").matches) {
        galleryViewport.value = "mobile";
        return;
    }

    if (window.matchMedia("(max-width: 1024px)").matches) {
        galleryViewport.value = "tablet";
        return;
    }

    galleryViewport.value = "desktop";
}

const { isRegistrationOpen } = useRegistrationWindow();

const mobileMenuOpen = ref(false);
const pageRoot = ref(null);
const honeycombRef = ref(null);

let revealObserver = null;
let glowObserver = null;
let typingTimer = null;
let typingCancelled = false;
let pageVisible = true;
let heroInView = true;

const taglineFull =
    "Together, we lead today for a better tomorrow.";
const typedTagline = ref("");

function clearTypingTimer() {
    if (typingTimer) {
        clearTimeout(typingTimer);
        typingTimer = null;
    }
}

function scheduleTyping(fn, delay) {
    clearTypingTimer();
    typingTimer = setTimeout(fn, delay);
}

function setHoneycombGlowing(active) {
    const el = honeycombRef.value;
    if (!el) {
        return;
    }

    el.classList.toggle("is-glowing", active);
}

function syncHoneycombGlow() {
    setHoneycombGlowing(pageVisible && heroInView);
}

function startTaglineTypingLoop() {
    typingCancelled = false;

    if (
        typeof window !== "undefined" &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
        typedTagline.value = taglineFull;
        return;
    }

    let index = 0;
    let deleting = false;

    const tick = () => {
        if (typingCancelled) {
            return;
        }

        if (!pageVisible) {
            scheduleTyping(tick, 400);
            return;
        }

        if (!deleting) {
            index += 1;
            typedTagline.value = taglineFull.slice(0, index);

            if (index >= taglineFull.length) {
                deleting = true;
                scheduleTyping(tick, 2000);
                return;
            }

            scheduleTyping(tick, 70);
            return;
        }

        index -= 1;
        typedTagline.value = taglineFull.slice(0, Math.max(index, 0));

        if (index <= 0) {
            deleting = false;
            scheduleTyping(tick, 650);
            return;
        }

        scheduleTyping(tick, 42);
    };

    typedTagline.value = "";
    scheduleTyping(tick, 900);
}

function onVisibilityChange() {
    pageVisible = document.visibilityState === "visible";
    syncHoneycombGlow();
}

function setupHoneycombGlow() {
    const el = honeycombRef.value;
    if (!el) {
        return;
    }

    if (
        typeof window !== "undefined" &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
        setHoneycombGlowing(false);
        return;
    }

    glowObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                heroInView = entry.isIntersecting;
                syncHoneycombGlow();
            });
        },
        { threshold: 0.05 },
    );

    glowObserver.observe(el);
    syncHoneycombGlow();
}

const vision =
    "Holistic 21st century value-laden skilled graduates are making a difference in community transformation beyond Bicol frontiers.";

const missionIntro = "To provide inexpensive quality education where:";

const missionPoints = [
    "dynamic students learn the 21st century skills and values in a friendly and gender-sensitive atmosphere;",
    "committed administration and staff ensure adequate resources for effective acquisition of knowledge, skill, and attitude;",
    "competent faculty delivers quality instruction in a conducive learning environment; and",
    "responsive stakeholders work in productive partnership for community development.",
];

const institutionalGoals = [
    "To provide learning opportunities that empower students to create positive change.",
    "To plan and implement relevant programs, projects, and activities for students' development.",
    "To improve quality of instruction through research, technology and innovations.",
    "To create equal opportunities to all sectors of the society for countryside development through excellent education.",
];

const coreValues = [
    "Competence",
    "Respect",
    "Service",
    "Excellence",
];

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

const features = [
    {
        title: "Secure Voting",
        desc: "End-to-end encrypted votes ensure your selection is private and tamper-proof.",
        icon: "M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z",
    },
    {
        title: "Real-time Results",
        desc: "Live vote tallying with instant results published as soon as polls close.",
        icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z",
    },
    {
        title: "Role Management",
        desc: "Separate admin and voter roles with tailored permissions and dashboards.",
        icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z",
    },
    {
        title: "Verified Voters",
        desc: "Only verified students can participate, keeping your elections fair and legitimate.",
        icon: "M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z",
    },
    {
        title: "Mobile Friendly",
        desc: "Vote from any device — desktop, tablet, or phone. Fully responsive design.",
        icon: "M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z",
    },
    {
        title: "Instant Access",
        desc: "Register in minutes and get immediate access to active elections and results.",
        icon: "M13 10V3L4 14h7v7l9-11h-7z",
    },
];

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function setupScrollReveals() {
    const root = pageRoot.value;
    if (!root) {
        return;
    }

    const targets = root.querySelectorAll(
        ".guest-reveal:not(.guest-reveal--immediate)",
    );

    if (
        typeof window !== "undefined" &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
        targets.forEach((el) => el.classList.add("guest-reveal--visible"));
        return;
    }

    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add("guest-reveal--visible");
                revealObserver?.unobserve(entry.target);
            });
        },
        {
            threshold: 0.12,
            rootMargin: "0px 0px -8% 0px",
        },
    );

    targets.forEach((el) => revealObserver.observe(el));
}

onMounted(async () => {
    await nextTick();
    syncGalleryViewport();
    window.addEventListener("resize", syncGalleryViewport);
    setupScrollReveals();
    setupHoneycombGlow();
    startTaglineTypingLoop();
    document.addEventListener("visibilitychange", onVisibilityChange);
});

onUnmounted(() => {
    typingCancelled = true;
    clearTypingTimer();
    revealObserver?.disconnect();
    revealObserver = null;
    glowObserver?.disconnect();
    glowObserver = null;
    window.removeEventListener("resize", syncGalleryViewport);
    document.removeEventListener("visibilitychange", onVisibilityChange);
});
</script>

<template>
    <Head title="Welcome">
        <link
            v-for="image in sscMembers.slice(0, 6)"
            :key="`preload-ssc-${image.id}`"
            rel="preload"
            as="image"
            :href="image.image_url"
        />
    </Head>

    <div ref="pageRoot" class="guest-shell">
        <header class="guest-header guest-reveal guest-reveal--immediate relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    class="min-h-16 flex items-center justify-between gap-3 py-1.5"
                >
                    <GuestHeaderBrand @click="closeMobileMenu" />

                    <nav class="hidden md:flex items-center gap-2">
                        <Link href="/about"
                            ><Button variant="ghost" size="sm"
                                >About</Button
                            ></Link
                        >
                        <Link href="/live-standing"
                            ><Button variant="ghost" size="sm"
                                >Live Standing</Button
                            ></Link
                        >
                        <Link href="/check-status"
                            ><Button variant="ghost" size="sm"
                                >Registration Status</Button
                            ></Link
                        >
                        <Link href="/reactivate"
                            ><Button variant="ghost" size="sm"
                                >Reactivate Account</Button
                            ></Link
                        >
                        <Link href="/reactivation-status"
                            ><Button variant="ghost" size="sm"
                                >Reactivation Status</Button
                            ></Link
                        >
                        <Link href="/login"
                            ><Button variant="ghost" size="sm"
                                >Log in</Button
                            ></Link
                        >
                        <Link v-if="isRegistrationOpen" href="/register"
                            ><Button variant="navy" size="sm"
                                >Register</Button
                            ></Link
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
                        href="/about"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >About</Button
                        ></Link
                    >
                    <Link
                        href="/live-standing"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >Live Standing</Button
                        ></Link
                    >
                    <Link
                        href="/check-status"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >Registration Status</Button
                        ></Link
                    >
                    <Link
                        href="/reactivate"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >Reactivate Account</Button
                        ></Link
                    >
                    <Link
                        href="/reactivation-status"
                        class="block"
                        @click="closeMobileMenu"
                        ><Button
                            variant="ghost"
                            size="sm"
                            class="w-full justify-start"
                            >Reactivation Status</Button
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
            <div
                ref="honeycombRef"
                class="guest-hero-honeycomb"
                aria-hidden="true"
            >
                <div class="guest-hero-honeycomb-glow">
                    <div class="guest-hero-honeycomb-glow-fill" />
                </div>
            </div>
            <div class="guest-hero-inner">
                <div class="guest-hero-stage">
                    <div class="guest-hero-dots" aria-hidden="true">
                        <span v-for="n in 16" :key="n" />
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
                            <p
                                class="guest-hero-tagline"
                                aria-label="Together, we lead today for a better tomorrow."
                            >
                                <span>{{ typedTagline }}</span
                                ><span
                                    class="guest-hero-tagline-cursor"
                                    aria-hidden="true"
                                    >|</span
                                >
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
                                <Link
                                    href="/live-standing"
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
                                            d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
                                        />
                                    </svg>
                                    Live Standing
                                </Link>
                            </div>

                            <RegistrationCountdown
                                variant="hero"
                                align="start"
                            />
                        </div>
                    </div>

                    <div class="guest-hero-emblem">
                        <PixelTransition
                            class-name="pixelated-image-card--hero"
                            pixel-color="#ffffff"
                            :grid-size="10"
                            :animation-step-duration="0.4"
                            aspect-ratio="100%"
                            :animation-only="true"
                            :once="false"
                        >
                            <template #first>
                                <img
                                    src="/images/hero-image/ssc.png"
                                    alt="Supreme Student Council emblem"
                                    class="pixelated-image-card--hero-image"
                                />
                            </template>
                            <template #second>
                                <img
                                    src="/images/hero-image/ssc.png"
                                    alt=""
                                    aria-hidden="true"
                                    class="pixelated-image-card--hero-image"
                                />
                            </template>
                        </PixelTransition>
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
            id="mission-vision"
            class="guest-mvg-section py-12 sm:py-16 lg:py-20 px-4 sm:px-6"
            aria-labelledby="guest-mvg-heading"
        >
            <div class="max-w-6xl mx-auto">
                <div
                    class="guest-mvg-header guest-reveal text-center mb-8 sm:mb-12 px-2"
                >
                    <p class="guest-mvg-eyebrow">Institutional Foundation</p>
                    <h2
                        id="guest-mvg-heading"
                        class="guest-mvg-title guest-title"
                    >
                        Mission, Vision, Goals &amp; Core Values
                    </h2>
                    <p class="guest-mvg-lead guest-muted">
                        Guiding principles that shape our commitment to
                        quality education and community transformation.
                    </p>
                </div>

                <div class="guest-mvg-grid">
                    <article
                        class="guest-mvg-card guest-mvg-card-vision guest-reveal"
                        style="--guest-reveal-delay: 0.08s"
                    >
                        <div class="guest-mvg-card-label">Vision</div>
                        <p class="guest-mvg-card-text">{{ vision }}</p>
                    </article>

                    <article
                        class="guest-mvg-card guest-mvg-card-mission guest-reveal"
                        style="--guest-reveal-delay: 0.16s"
                    >
                        <div class="guest-mvg-card-label">Mission</div>
                        <p class="guest-mvg-card-text guest-mvg-mission-intro">
                            {{ missionIntro }}
                        </p>
                        <ul class="guest-mvg-list">
                            <li
                                v-for="point in missionPoints"
                                :key="point"
                                class="guest-mvg-list-item"
                            >
                                {{ point }}
                            </li>
                        </ul>
                    </article>

                    <article
                        class="guest-mvg-card guest-mvg-card-goals guest-reveal"
                        style="--guest-reveal-delay: 0.24s"
                    >
                        <div class="guest-mvg-card-label">Goals</div>
                        <ul class="guest-mvg-list">
                            <li
                                v-for="goal in institutionalGoals"
                                :key="goal"
                                class="guest-mvg-list-item"
                            >
                                {{ goal }}
                            </li>
                        </ul>
                    </article>
                </div>

                <div
                    class="guest-mvg-values-wrap guest-reveal"
                    style="--guest-reveal-delay: 0.12s"
                >
                    <p class="guest-mvg-values-heading">Core Values</p>
                    <div class="guest-mvg-values">
                        <div
                            v-for="(value, index) in coreValues"
                            :key="value"
                            class="guest-mvg-value"
                            :class="{ 'guest-mvg-value-divider': index > 0 }"
                        >
                            <span class="guest-mvg-value-text">{{ value }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="domeGalleryImages.length"
            id="gallery"
            class="guest-dome-gallery"
            aria-labelledby="guest-gallery-heading"
        >
            <div class="guest-dome-gallery-header guest-reveal text-center px-4 sm:px-6">
                <p class="guest-mvg-eyebrow">Moments &amp; Memories</p>
                <h2
                    id="guest-gallery-heading"
                    class="guest-mvg-title guest-title"
                >
                    Gallery
                </h2>
                <p class="guest-mvg-lead guest-muted">
                    A closer look at our community, events, and campus life.
                </p>
            </div>

            <div
                v-if="isCircularGallery"
                class="guest-circular-gallery-stage"
            >
                <CircularGallery
                    :items="circularGalleryItems"
                    :bend="circularBend"
                    text-color="#0f172a"
                    :border-radius="0.05"
                    :scroll-speed="circularScrollSpeed"
                    :scroll-ease="0.02"
                    :font="circularFont"
                />
            </div>

            <div v-else class="guest-dome-gallery-stage">
                <div class="guest-dome-gallery-frame">
                    <DomeGallery
                        :images="domeGalleryImages"
                        :fit="0.92"
                        :min-radius="0"
                        :max-vertical-rotation-deg="0"
                        :segments="34"
                        :drag-dampening="2"
                        :grayscale="false"
                        image-border-radius="30px"
                        opened-image-border-radius="30px"
                        opened-image-width="250px"
                        opened-image-height="350px"
                        overlay-blur-color="#ffffff"
                    />
                </div>
            </div>
        </section>

        <section
            id="features"
            class="guest-features-section py-12 sm:py-16 lg:py-20 px-4 sm:px-6 bg-white"
        >
            <div class="max-w-6xl mx-auto">
                <div class="guest-reveal text-center mb-8 sm:mb-12 px-2">
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
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        class="guest-card guest-reveal p-5 sm:p-6"
                        :style="{
                            '--guest-reveal-delay': `${0.06 + index * 0.08}s`,
                        }"
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
            class="guest-cta-section py-12 sm:py-16 lg:py-20 px-4 sm:px-6 text-center bg-white"
        >
            <div class="guest-reveal max-w-xl mx-auto">
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
                    <Button v-else size="lg" class="w-full sm:min-w-36" disabled
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

        <AivaFloatingAssistant />
    </div>
</template>
