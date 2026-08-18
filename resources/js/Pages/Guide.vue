<script setup>
import { nextTick, onMounted, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import GuestHeaderBrand from "@/Components/GuestHeaderBrand.vue";
import { useRegistrationWindow } from "@/composables/useRegistrationWindow";

const { isRegistrationOpen } = useRegistrationWindow();

const pageRoot = ref(null);
const registerGuideVideoSrc = "/video/guide.mp4";
const voteGuideVideoSrc = "/video/guide1.mp4";
const guideVideo = ref(null);
const voteGuideVideo = ref(null);
const guideVideoPaused = ref(true);
const voteGuideVideoPaused = ref(true);

function onGuideVideoPlay() {
    guideVideoPaused.value = false;
    voteGuideVideo.value?.pause();
}

function onGuideVideoPause() {
    guideVideoPaused.value = true;
}

function onVoteGuideVideoPlay() {
    voteGuideVideoPaused.value = false;
    guideVideo.value?.pause();
}

function onVoteGuideVideoPause() {
    voteGuideVideoPaused.value = true;
}

async function playGuideVideo() {
    const el = guideVideo.value;
    if (!el) {
        return;
    }

    voteGuideVideo.value?.pause();

    try {
        await el.play();
    } catch {
        guideVideoPaused.value = true;
    }
}

async function playVoteGuideVideo() {
    const el = voteGuideVideo.value;
    if (!el) {
        return;
    }

    guideVideo.value?.pause();

    try {
        await el.play();
    } catch {
        voteGuideVideoPaused.value = true;
    }
}

const registerSteps = [
    {
        step: "01",
        title: "Confirm you are on campus",
        desc: "If campus location access is enabled, the portal asks you to share your location first. You must be inside the allowed campus area before you can continue.",
        tip: "If you see “Outside campus area,” move closer to campus Wi‑Fi or GPS coverage and try again.",
    },
    {
        step: "02",
        title: "Open Register during the registration window",
        desc: "Go to Register from the home page. Registration is only available while the registration period is open. When it is closed, the Register button stays disabled.",
        tip: "Watch the countdown on the welcome page so you know when registration opens or closes.",
    },
    {
        step: "03",
        title: "Enter your student account details",
        desc: "Provide your full name, school email, student ID number, department, course, year level, and a password (with confirmation). Your course and year level must match what the system allows for that program.",
        tip: "Use your real student email and ID. System admin or committee emails cannot be used for voter registration.",
    },
    {
        step: "04",
        title: "Capture your student ID photo",
        desc: "After Step 1 is saved, you are taken to ID scan. Take a clear photo of your student ID. The image quality must be acceptable before you can continue.",
        tip: "Use good lighting, keep the ID flat and fully visible, and avoid blurry shots. A failed capture means you need to try again.",
    },
    {
        step: "05",
        title: "Save your Voter ID",
        desc: "When registration is submitted, your account is created and you receive a Voter ID in the format VID-YYYY-#####. Keep this ID — you will need it to check your registration status.",
        tip: "Your account starts as pending email verification (pending OTP) until you finish the next step.",
    },
    {
        step: "06",
        title: "Verify your email with the link and OTP",
        desc: "Open the verification email, click the verify link, then enter the 6-digit code. Codes expire in 10 minutes and allow up to 5 attempts. You can request a new code if needed.",
        tip: "Check spam or junk folders. After a successful OTP, your email is verified and identity processing begins.",
    },
    {
        step: "07",
        title: "Wait for identity verification",
        desc: "The system processes your ID image (OCR) and fraud checks. High-confidence matches can be approved automatically. Otherwise, an administrator must verify your account before you can log in.",
        tip: "You cannot log in until both email verification and account verification are complete.",
    },
    {
        step: "08",
        title: "Check status, then log in",
        desc: "Use Registration Status on the home page and enter your Voter ID to track progress. When your account is verified, log in with your email and password to access elections.",
        tip: "If login says your account is pending verification, wait for admin approval or check status again. Expired accounts use Reactivate Account instead.",
    },
];

const voteSteps = [
    {
        step: "01",
        title: "Log in with a verified voter account",
        desc: "Only verified voters can cast ballots. After logging in, open Elections from your voter dashboard.",
        tip: "If Elections shows a pending verification message, your account is not approved yet.",
    },
    {
        step: "02",
        title: "Choose an election that is open for voting",
        desc: "You will see elections that are scheduled or active. You can only vote when the voting window is open — between the election’s voting start and end times — and while the election is not closed.",
        tip: "If voting is not open yet, wait for the voting period. If it has ended, the ballot can no longer be submitted.",
    },
    {
        step: "03",
        title: "Select one candidate for every position",
        desc: "Start your ballot and choose exactly one candidate per position. Every position that has candidates must have a selection before you can submit.",
        tip: "You cannot leave a position blank, and you cannot pick more than one candidate for the same position.",
    },
    {
        step: "04",
        title: "Confirm and submit your ballot",
        desc: "Review your selections in the confirmation dialog, then confirm and submit. Your ballot is queued for processing — you will see that it is being processed.",
        tip: "Each voter may submit only one ballot per election. Double voting is blocked by the system.",
    },
    {
        step: "05",
        title: "Wait for processing, then view your receipt",
        desc: "After submission, the system records your votes and creates a ballot receipt. When processing finishes, you are taken to your receipt page where you can review your selections and download a PDF.",
        tip: "You can also find past ballots and receipts later under My Votes.",
    },
];

const reminders = [
    {
        title: "Registration window",
        desc: "Account creation is only allowed while registration is open. Outside that window, start again when it reopens.",
    },
    {
        title: "Verification before login",
        desc: "Email OTP and identity approval must both succeed before you can sign in and vote.",
    },
    {
        title: "One ballot per election",
        desc: "Once your ballot is submitted and processed, you cannot vote again in that same election.",
    },
    {
        title: "Keep your Voter ID",
        desc: "Use VID-YYYY-##### on the Registration Status page whenever you need an update on approval progress.",
    },
];

function setupGuideReveals() {
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

    requestAnimationFrame(() => {
        targets.forEach((el) => el.classList.add("guest-reveal--visible"));
    });
}

onMounted(async () => {
    await nextTick();
    setupGuideReveals();
});
</script>

<template>
    <Head title="Guide" />

    <div ref="pageRoot" class="guest-shell">
        <header
            class="guest-header guest-about-header guest-reveal guest-reveal--immediate"
        >
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="guest-about-topbar">
                    <div class="guest-about-topbar-brand">
                        <GuestHeaderBrand />
                    </div>

                    <Link
                        href="/"
                        class="guest-about-home-btn"
                        aria-label="Back to home"
                    >
                        <svg
                            class="h-4 w-4 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        <span class="guest-about-home-label">Home</span>
                    </Link>

                    <div class="guest-about-auth">
                        <Link href="/login" class="guest-about-auth-link">
                            <Button variant="outline" size="sm" class="w-full"
                                >Log in</Button
                            >
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="guest-about">
            <section class="guest-about-hero">
                <div class="guest-about-hero-inner">
                    <p
                        class="guest-about-eyebrow guest-reveal"
                        style="--guest-reveal-delay: 0.08s"
                    >
                        Voter Guide
                    </p>
                    <h1
                        class="guest-about-title guest-reveal"
                        style="--guest-reveal-delay: 0.18s"
                    >
                        How to register and vote
                    </h1>
                    <p
                        class="guest-about-lead guest-reveal"
                        style="--guest-reveal-delay: 0.3s"
                    >
                        Follow these steps exactly as SSCEVS processes your
                        registration, verification, and ballot — from creating
                        your account to downloading your voting receipt.
                    </p>
                    <div
                        class="guest-guide-jump guest-reveal"
                        style="--guest-reveal-delay: 0.42s"
                    >
                        <a href="#how-to-register" class="guest-guide-jump-link"
                            >How to register</a
                        >
                        <a href="#how-to-vote" class="guest-guide-jump-link"
                            >How to vote</a
                        >
                    </div>
                </div>
            </section>

            <section
                id="how-to-register"
                class="guest-about-section guest-about-section-alt"
            >
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <h2 class="guest-about-heading">How to register</h2>
                        <p class="guest-about-copy">
                            Registration has two form steps (account details,
                            then ID capture), followed by email OTP and identity
                            verification before you can log in.
                        </p>
                    </div>

                    <figure
                        id="how-to-register-video"
                        class="guest-guide-video guest-reveal"
                        style="--guest-reveal-delay: 0.24s"
                    >
                        <div class="guest-guide-video-frame">
                            <video
                                ref="guideVideo"
                                class="guest-guide-video-player"
                                controls
                                playsinline
                                preload="auto"
                                @play="onGuideVideoPlay"
                                @pause="onGuideVideoPause"
                                @ended="onGuideVideoPause"
                            >
                                <source
                                    :src="registerGuideVideoSrc"
                                    type="video/mp4"
                                />
                                Your browser does not support this video. You
                                can
                                <a :href="registerGuideVideoSrc"
                                    >download the registration guide</a
                                >
                                instead.
                            </video>
                            <button
                                v-show="guideVideoPaused"
                                type="button"
                                class="guest-guide-video-play"
                                aria-label="Play registration guide"
                                @click="playGuideVideo"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path d="M8 5.14v13.72L19 12 8 5.14z" />
                                </svg>
                            </button>
                        </div>
                        <figcaption class="guest-guide-video-caption">
                            Watch the registration walkthrough in full quality,
                            then follow the steps below.
                        </figcaption>
                    </figure>

                    <ol class="guest-guide-steps">
                        <li
                            v-for="(item, index) in registerSteps"
                            :key="item.step"
                            class="guest-guide-step guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.28 + index * 0.06}s`,
                            }"
                        >
                            <span
                                class="guest-guide-step-num"
                                aria-hidden="true"
                                >{{ item.step }}</span
                            >
                            <div class="guest-guide-step-body">
                                <h3 class="guest-guide-step-title">
                                    {{ item.title }}
                                </h3>
                                <p class="guest-guide-step-desc">
                                    {{ item.desc }}
                                </p>
                                <p class="guest-guide-step-tip">
                                    {{ item.tip }}
                                </p>
                            </div>
                        </li>
                    </ol>

                    <div
                        class="guest-guide-actions guest-reveal"
                        style="--guest-reveal-delay: 0.4s"
                    >
                        <Link
                            v-if="isRegistrationOpen"
                            href="/register"
                            class="guest-hero-btn guest-hero-btn-primary"
                        >
                            Start registration
                        </Link>
                        <span
                            v-else
                            class="guest-hero-btn guest-hero-btn-primary guest-hero-btn-disabled"
                            aria-disabled="true"
                        >
                            Registration closed
                        </span>
                        <Link
                            href="/check-status"
                            class="guest-hero-btn guest-hero-btn-outline"
                        >
                            Check registration status
                        </Link>
                    </div>
                </div>
            </section>

            <section id="how-to-vote" class="guest-about-section">
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <h2 class="guest-about-heading">How to vote</h2>
                        <p class="guest-about-copy">
                            Voting opens only for verified voters during each
                            election’s voting period. You select one candidate
                            per position, submit once, then receive a receipt.
                        </p>
                    </div>

                    <figure
                        id="how-to-vote-video"
                        class="guest-guide-video guest-reveal"
                        style="--guest-reveal-delay: 0.24s"
                    >
                        <div class="guest-guide-video-frame">
                            <video
                                ref="voteGuideVideo"
                                class="guest-guide-video-player"
                                controls
                                playsinline
                                preload="auto"
                                @play="onVoteGuideVideoPlay"
                                @pause="onVoteGuideVideoPause"
                                @ended="onVoteGuideVideoPause"
                            >
                                <source
                                    :src="voteGuideVideoSrc"
                                    type="video/mp4"
                                />
                                Your browser does not support this video. You
                                can
                                <a :href="voteGuideVideoSrc"
                                    >download the voting guide</a
                                >
                                instead.
                            </video>
                            <button
                                v-show="voteGuideVideoPaused"
                                type="button"
                                class="guest-guide-video-play"
                                aria-label="Play voting guide"
                                @click="playVoteGuideVideo"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path d="M8 5.14v13.72L19 12 8 5.14z" />
                                </svg>
                            </button>
                        </div>
                        <figcaption class="guest-guide-video-caption">
                            Watch the voting walkthrough in full quality, then
                            follow the steps below.
                        </figcaption>
                    </figure>

                    <ol class="guest-guide-steps">
                        <li
                            v-for="(item, index) in voteSteps"
                            :key="item.step"
                            class="guest-guide-step guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.28 + index * 0.06}s`,
                            }"
                        >
                            <span
                                class="guest-guide-step-num"
                                aria-hidden="true"
                                >{{ item.step }}</span
                            >
                            <div class="guest-guide-step-body">
                                <h3 class="guest-guide-step-title">
                                    {{ item.title }}
                                </h3>
                                <p class="guest-guide-step-desc">
                                    {{ item.desc }}
                                </p>
                                <p class="guest-guide-step-tip">
                                    {{ item.tip }}
                                </p>
                            </div>
                        </li>
                    </ol>

                    <div
                        class="guest-guide-actions guest-reveal"
                        style="--guest-reveal-delay: 0.4s"
                    >
                        <Link
                            href="/login"
                            class="guest-hero-btn guest-hero-btn-primary"
                        >
                            Log in to vote
                        </Link>
                        <Link
                            href="/live-standing"
                            class="guest-hero-btn guest-hero-btn-outline"
                        >
                            View Live Standing
                        </Link>
                    </div>
                </div>
            </section>

            <section class="guest-about-section guest-about-section-alt">
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <h2 class="guest-about-heading">Important reminders</h2>
                        <p class="guest-about-copy">
                            These rules come from how SSCEVS actually handles
                            registration and ballots.
                        </p>
                    </div>

                    <div class="guest-about-feature-grid">
                        <article
                            v-for="(item, index) in reminders"
                            :key="item.title"
                            class="guest-about-feature guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.28 + index * 0.08}s`,
                            }"
                        >
                            <h3 class="guest-about-feature-title">
                                {{ item.title }}
                            </h3>
                            <p class="guest-about-feature-desc">
                                {{ item.desc }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="guest-about-section">
                <div
                    class="guest-about-container guest-about-cta guest-reveal"
                    style="--guest-reveal-delay: 0.2s"
                >
                    <h2 class="guest-about-heading">Ready to get started?</h2>
                    <p class="guest-about-copy">
                        Register when the window is open, finish verification,
                        then cast your ballot during the election period.
                    </p>
                    <div class="guest-about-cta-actions">
                        <Link
                            v-if="isRegistrationOpen"
                            href="/register"
                            class="guest-hero-btn guest-hero-btn-primary"
                        >
                            Register
                        </Link>
                        <span
                            v-else
                            class="guest-hero-btn guest-hero-btn-primary guest-hero-btn-disabled"
                            aria-disabled="true"
                        >
                            Register
                        </span>
                        <Link
                            href="/login"
                            class="guest-hero-btn guest-hero-btn-outline"
                        >
                            Log in
                        </Link>
                        <Link
                            href="/about"
                            class="guest-hero-btn guest-hero-btn-outline"
                        >
                            About SSCEVS
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <footer
            class="guest-footer guest-reveal py-5 sm:py-6 px-4 text-center bg-white"
            style="--guest-reveal-delay: 0.28s"
        >
            <p class="text-xs leading-relaxed">
                &copy; {{ new Date().getFullYear() }} SSCEVS. All rights
                reserved.
            </p>
        </footer>
    </div>
</template>
