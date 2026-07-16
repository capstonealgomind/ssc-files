<script setup>
import { nextTick, onMounted, ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
import GuestHeaderBrand from "@/Components/GuestHeaderBrand.vue";
import { useRegistrationWindow } from "@/composables/useRegistrationWindow";

const { isRegistrationOpen } = useRegistrationWindow();

const pageRoot = ref(null);

const pillars = [
    { title: "Unity", subtitle: "Stronger Together" },
    { title: "Integrity", subtitle: "Leadership with Honor" },
    { title: "Service", subtitle: "Commitment to All" },
    { title: "Excellence", subtitle: "Strive. Achieve. Inspire." },
];

const features = [
    {
        title: "Secure Voting",
        desc: "Encrypted ballots keep every vote private and tamper-proof from cast to count.",
    },
    {
        title: "Verified Voters",
        desc: "Only verified students can register and vote, protecting election integrity.",
    },
    {
        title: "Live Standing",
        desc: "Follow real-time standings and turnout as ballots are recorded.",
    },
    {
        title: "Transparent Process",
        desc: "Clear registration, voting, receipt, and results flows for every student.",
    },
    {
        title: "Role-Based Access",
        desc: "Admin, committee, and voter dashboards with the right tools for each role.",
    },
    {
        title: "AIVA Assistant",
        desc: "Get quick help on registration, login, voting, and portal guidance anytime.",
    },
];

const howItWorks = [
    {
        step: "01",
        title: "Register",
        desc: "Create an account with verified student credentials during the registration window.",
    },
    {
        step: "02",
        title: "Verify",
        desc: "Complete email and identity checks so only eligible voters can participate.",
    },
    {
        step: "03",
        title: "Vote",
        desc: "Cast your ballot securely during the election period from any device.",
    },
    {
        step: "04",
        title: "Track",
        desc: "View Live Standing, receipts, and official results with full transparency.",
    },
];

const vision =
    "Holistic 21st century value-laden skilled graduates are making a difference in community transformation beyond Bicol frontiers.";

const missionIntro = "To provide inexpensive quality education where:";

const missionPoints = [
    "dynamic students learn the 21st century skills and values in a friendly and gender-sensitive atmosphere;",
    "committed administration and staff ensure adequate resources for effective acquisition of knowledge, skill, and attitude;",
    "competent faculty delivers quality instruction in a conducive learning environment; and",
    "responsive stakeholders work in productive partnership for community development.",
];

const goals = [
    "To provide learning opportunities that empower students to create positive change.",
    "To plan and implement relevant programs, projects, and activities for students' development.",
    "To improve quality of instruction through research, technology and innovations.",
    "To create equal opportunities to all sectors of the society for countryside development through excellent education.",
];

const coreValues = ["Competence", "Respect", "Service", "Excellence"];

const researchers = [
    {
        name: "Vince Joseph B. Catangui",
        role: "Project Head / Database Designer (PH/DD)",
        image: "/images/members/Vince Joseph B. Catangui.jpg",
        facebook: "https://www.facebook.com/Vincejosephcatangui29",
    },
    {
        name: "Angel Brigeth M. Serdan",
        role: "Manuscript / Programmer / Software Engineer",
        image: "/images/members/Angel Brigeth M. Serdan.jpg",
        facebook: "https://www.facebook.com/ruby.reyez.5855",
    },
    {
        name: "Mark Joseph Breboneria Baraero",
        role: "UI Designer / QA Tester / Programmer",
        image: "/images/members/Mark Joseph Breboneria Baraero.jpg",
        facebook: "https://www.facebook.com/markjoseph.baraero",
    },
    {
        name: "Rico Bobis Puso",
        role: "Software Engineer / Programmer",
        image: "/images/members/Rico Bobis  Puso.jpg",
        facebook: "https://www.facebook.com/rico.puso.16",
    },
    {
        name: "Shaina Kaye Realin",
        role: "Manuscript Writer",
        image: "/images/members/Shaina Kaye Realin.jpg",
        facebook: "https://www.facebook.com/shainakaye.penarandarealin",
    },
];

function researcherImageUrl(path) {
    const parts = path.split("/");
    const file = parts.pop();
    return `${parts.join("/")}/${encodeURIComponent(file)}`;
}

function setupAboutReveals() {
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
    setupAboutReveals();
});
</script>

<template>
    <Head title="About" />

    <div ref="pageRoot" class="guest-shell">
        <header class="guest-header guest-about-header guest-reveal guest-reveal--immediate">
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
                        About SSCEVS
                    </p>
                    <h1
                        class="guest-about-title guest-reveal"
                        style="--guest-reveal-delay: 0.18s"
                    >
                        Supreme Student Council Electronic Voting System
                    </h1>
                    <p
                        class="guest-about-lead guest-reveal"
                        style="--guest-reveal-delay: 0.3s"
                    >
                        SSCEVS is the official electronic voting platform for
                        Supreme Student Council elections — built for secure,
                        transparent, and accessible student democracy.
                    </p>
                    <p
                        class="guest-about-tagline guest-reveal"
                        style="--guest-reveal-delay: 0.42s"
                    >
                        One Vote. One Voice. One Council.
                    </p>
                </div>
            </section>

            <section class="guest-about-section">
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.5s"
                    >
                        <h2 class="guest-about-heading">What is SSCEVS?</h2>
                        <p class="guest-about-copy">
                            SSCEVS helps students register, verify, vote, and
                            follow election outcomes online. It supports voters,
                            administrators, and election committees with
                            role-based tools that keep the process fair,
                            auditable, and easy to use on any device.
                        </p>
                    </div>

                    <div class="guest-about-pillars">
                        <div
                            v-for="(pillar, index) in pillars"
                            :key="pillar.title"
                            class="guest-about-pillar guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.58 + index * 0.08}s`,
                            }"
                        >
                            <p class="guest-about-pillar-title">
                                {{ pillar.title }}
                            </p>
                            <p class="guest-about-pillar-sub">
                                {{ pillar.subtitle }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="guest-about-section guest-about-section-alt">
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <h2 class="guest-about-heading">What you can do</h2>
                        <p class="guest-about-copy">
                            Everything you need for a trusted campus election —
                            from registration to live results.
                        </p>
                    </div>

                    <div class="guest-about-feature-grid">
                        <article
                            v-for="(feature, index) in features"
                            :key="feature.title"
                            class="guest-about-feature guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.28 + index * 0.08}s`,
                            }"
                        >
                            <h3 class="guest-about-feature-title">
                                {{ feature.title }}
                            </h3>
                            <p class="guest-about-feature-desc">
                                {{ feature.desc }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="guest-about-section">
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <h2 class="guest-about-heading">How it works</h2>
                        <p class="guest-about-copy">
                            A clear path from registration to results for every
                            eligible student.
                        </p>
                    </div>

                    <div class="guest-about-steps">
                        <article
                            v-for="(item, index) in howItWorks"
                            :key="item.step"
                            class="guest-about-step guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.28 + index * 0.08}s`,
                            }"
                        >
                            <span class="guest-about-step-num">{{
                                item.step
                            }}</span>
                            <h3 class="guest-about-step-title">
                                {{ item.title }}
                            </h3>
                            <p class="guest-about-step-desc">{{ item.desc }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="guest-about-section guest-about-section-alt">
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <h2 class="guest-about-heading">
                            Mission, Vision, Goals &amp; Core Values
                        </h2>
                        <p class="guest-about-copy">
                            Institutional foundations that guide our commitment
                            to quality education and community transformation.
                        </p>
                    </div>

                    <div class="guest-mvg-grid">
                        <article
                            class="guest-mvg-card guest-mvg-card-vision guest-reveal"
                            style="--guest-reveal-delay: 0.28s"
                        >
                            <div class="guest-mvg-card-label">Vision</div>
                            <p class="guest-mvg-card-text">{{ vision }}</p>
                        </article>

                        <article
                            class="guest-mvg-card guest-mvg-card-mission guest-reveal"
                            style="--guest-reveal-delay: 0.36s"
                        >
                            <div class="guest-mvg-card-label">Mission</div>
                            <p
                                class="guest-mvg-card-text guest-mvg-mission-intro"
                            >
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
                            style="--guest-reveal-delay: 0.44s"
                        >
                            <div class="guest-mvg-card-label">Goals</div>
                            <ul class="guest-mvg-list">
                                <li
                                    v-for="goal in goals"
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
                        style="--guest-reveal-delay: 0.52s"
                    >
                        <p class="guest-mvg-values-heading">Core Values</p>
                        <div class="guest-mvg-values">
                            <div
                                v-for="(value, index) in coreValues"
                                :key="value"
                                class="guest-mvg-value"
                                :class="{
                                    'guest-mvg-value-divider': index > 0,
                                }"
                            >
                                <span class="guest-mvg-value-text">{{
                                    value
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="guest-about-section guest-about-section-alt"
                aria-labelledby="guest-about-credits-heading"
            >
                <div class="guest-about-container">
                    <div
                        class="guest-about-section-header guest-about-credits-header guest-reveal"
                        style="--guest-reveal-delay: 0.2s"
                    >
                        <p class="guest-about-eyebrow">Capstone Project</p>
                        <p class="guest-about-team-name">𝒜𝐿𝒢𝒪𝑀𝐼𝒩𝒟</p>
                        <h2
                            id="guest-about-credits-heading"
                            class="guest-about-heading"
                        >
                            Researchers
                        </h2>
                        <p class="guest-about-copy">
                            SSCEVS was developed as a capstone project by team
                            <strong>𝒜𝐿𝒢𝒪𝑀𝐼𝒩𝒟</strong>. Meet the researchers
                            below.
                        </p>
                    </div>

                    <div class="guest-about-credits-grid">
                        <article
                            v-for="(researcher, index) in researchers"
                            :key="researcher.name"
                            class="guest-about-credit-card guest-reveal"
                            :style="{
                                '--guest-reveal-delay': `${0.28 + index * 0.08}s`,
                            }"
                        >
                            <a
                                :href="researcher.facebook"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="guest-about-credit-link"
                                :aria-label="`${researcher.name} on Facebook`"
                            >
                                <div class="guest-about-credit-photo-wrap">
                                    <img
                                        :src="
                                            researcherImageUrl(researcher.image)
                                        "
                                        :alt="researcher.name"
                                        class="guest-about-credit-photo"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                </div>
                                <h3 class="guest-about-credit-name">
                                    {{ researcher.name }}
                                </h3>
                                <p class="guest-about-credit-role">
                                    {{ researcher.role }}
                                </p>
                                <span class="guest-about-credit-facebook">
                                    <svg
                                        class="guest-about-credit-facebook-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill="currentColor"
                                            d="M22 12.07C22 6.48 17.52 2 11.93 2S1.86 6.48 1.86 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.02H7.9v-2.91h2.4V9.84c0-2.37 1.4-3.68 3.56-3.68 1.03 0 2.11.18 2.11.18v2.32h-1.19c-1.17 0-1.54.73-1.54 1.48v1.78h2.62l-.42 2.91h-2.2V22c4.78-.75 8.44-4.91 8.44-9.93z"
                                        />
                                    </svg>
                                    Facebook
                                </span>
                            </a>
                        </article>
                    </div>
                </div>
            </section>

            <section class="guest-about-section">
                <div
                    class="guest-about-container guest-about-cta guest-reveal"
                    style="--guest-reveal-delay: 0.2s"
                >
                    <h2 class="guest-about-heading">Ready to participate?</h2>
                    <p class="guest-about-copy">
                        Join SSCEVS to register, vote securely, and help shape
                        student leadership.
                    </p>
                    <div class="guest-about-cta-actions">
                        <Link
                            v-if="isRegistrationOpen"
                            href="/register"
                            class="guest-hero-btn guest-hero-btn-primary"
                        >
                            Get Started
                        </Link>
                        <span
                            v-else
                            class="guest-hero-btn guest-hero-btn-primary guest-hero-btn-disabled"
                            aria-disabled="true"
                        >
                            Get Started
                        </span>
                        <Link
                            href="/login"
                            class="guest-hero-btn guest-hero-btn-outline"
                        >
                            Log in
                        </Link>
                        <Link
                            href="/live-standing"
                            class="guest-hero-btn guest-hero-btn-outline"
                        >
                            Live Standing
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
