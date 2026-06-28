<script setup>
import { Head, Link } from "@inertiajs/vue3";
import Button from "@/Components/ui/Button.vue";
</script>

<template>
    <Head title="Welcome" />

    <div
        class="min-h-screen flex flex-col"
        style="background-color: hsl(0 0% 100%)"
    >
        <!-- Navigation -->
        <header
            class="sticky top-0 z-50 border-b backdrop-blur-sm"
            style="
                background-color: rgba(255, 255, 255, 0.95);
                border-color: hsl(240 5.9% 90%);
            "
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="h-7 w-7 rounded-md flex items-center justify-center"
                        style="background-color: hsl(240 5.9% 10%)"
                    >
                        <svg
                            class="h-4 w-4"
                            style="color: hsl(0 0% 98%)"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                    <span
                        class="font-semibold text-sm"
                        style="color: hsl(240 10% 3.9%)"
                        >SSCEVS</span
                    >
                </div>
                <nav class="flex items-center gap-2">
                    <Link href="/check-status">
                        <Button variant="ghost" size="sm">Check Status</Button>
                    </Link>
                    <Link href="/login">
                        <Button variant="ghost" size="sm">Log in</Button>
                    </Link>
                    <Link href="/register">
                        <Button size="sm">Register</Button>
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <section
            class="flex-1 flex flex-col items-center justify-center text-center px-4 py-24 sm:py-32"
        >
            <!-- Badge -->
            <div
                class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium mb-8"
                style="
                    border-color: hsl(240 5.9% 90%);
                    color: hsl(240 3.8% 46.1%);
                    background-color: hsl(240 4.8% 95.9%);
                "
            >
                <span
                    class="h-1.5 w-1.5 rounded-full"
                    style="background-color: hsl(142 71% 45%)"
                ></span>
                Secure Student Council Electronic Voting System
            </div>

            <h1
                class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight mb-6 max-w-3xl"
                style="color: hsl(240 10% 3.9%)"
            >
                Your voice matters.
                <span style="color: hsl(240 3.8% 46.1%)">
                    Vote with confidence.</span
                >
            </h1>

            <p
                class="text-base sm:text-lg max-w-xl mb-10 leading-relaxed"
                style="color: hsl(240 3.8% 46.1%)"
            >
                A modern, secure, and transparent electronic voting platform for
                your student council elections. Register, verify, and cast your
                vote — all in one place.
            </p>

            <div
                class="flex flex-col sm:flex-row gap-3 items-center justify-center"
            >
                <Link href="/register">
                    <Button size="lg" class="min-w-36">Get Started</Button>
                </Link>
                <Link href="/login">
                    <Button variant="outline" size="lg" class="min-w-36"
                        >Log in</Button
                    >
                </Link>
            </div>
        </section>

        <!-- Features Section -->
        <section
            class="py-20 px-4"
            style="background-color: hsl(240 4.8% 95.9%)"
        >
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <h2
                        class="text-2xl sm:text-3xl font-bold mb-3"
                        style="color: hsl(240 10% 3.9%)"
                    >
                        Everything you need for secure elections
                    </h2>
                    <p
                        class="text-sm sm:text-base"
                        style="color: hsl(240 3.8% 46.1%)"
                    >
                        Built with transparency, security, and simplicity in
                        mind.
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <!-- Feature 1 -->
                    <div
                        class="rounded-xl border p-6"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-4"
                            style="background-color: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-5 w-5"
                                style="color: hsl(240 5.9% 10%)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-2"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Secure Voting
                        </h3>
                        <p
                            class="text-sm leading-relaxed"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            End-to-end encrypted votes ensure your selection is
                            private and tamper-proof.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div
                        class="rounded-xl border p-6"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-4"
                            style="background-color: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-5 w-5"
                                style="color: hsl(240 5.9% 10%)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-2"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Real-time Results
                        </h3>
                        <p
                            class="text-sm leading-relaxed"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Live vote tallying with instant results published as
                            soon as polls close.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div
                        class="rounded-xl border p-6"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-4"
                            style="background-color: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-5 w-5"
                                style="color: hsl(240 5.9% 10%)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-2"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Role Management
                        </h3>
                        <p
                            class="text-sm leading-relaxed"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Separate admin and voter roles with tailored
                            permissions and dashboards.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div
                        class="rounded-xl border p-6"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-4"
                            style="background-color: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-5 w-5"
                                style="color: hsl(240 5.9% 10%)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-2"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Verified Voters
                        </h3>
                        <p
                            class="text-sm leading-relaxed"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Only verified students can participate, keeping your
                            elections fair and legitimate.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div
                        class="rounded-xl border p-6"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-4"
                            style="background-color: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-5 w-5"
                                style="color: hsl(240 5.9% 10%)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-2"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Mobile Friendly
                        </h3>
                        <p
                            class="text-sm leading-relaxed"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Vote from any device — desktop, tablet, or phone.
                            Fully responsive design.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div
                        class="rounded-xl border p-6"
                        style="
                            background-color: hsl(0 0% 100%);
                            border-color: hsl(240 5.9% 90%);
                        "
                    >
                        <div
                            class="h-10 w-10 rounded-lg flex items-center justify-center mb-4"
                            style="background-color: hsl(240 4.8% 95.9%)"
                        >
                            <svg
                                class="h-5 w-5"
                                style="color: hsl(240 5.9% 10%)"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="font-semibold mb-2"
                            style="color: hsl(240 10% 3.9%)"
                        >
                            Instant Access
                        </h3>
                        <p
                            class="text-sm leading-relaxed"
                            style="color: hsl(240 3.8% 46.1%)"
                        >
                            Register in minutes and get immediate access to
                            active elections and results.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="py-20 px-4 text-center"
            style="background-color: hsl(0 0% 100%)"
        >
            <div class="max-w-xl mx-auto">
                <h2
                    class="text-2xl sm:text-3xl font-bold mb-4"
                    style="color: hsl(240 10% 3.9%)"
                >
                    Ready to participate?
                </h2>
                <p
                    class="text-sm sm:text-base mb-8"
                    style="color: hsl(240 3.8% 46.1%)"
                >
                    Join thousands of students who use SSCEVS for transparent
                    and secure school elections.
                </p>
                <div
                    class="flex flex-col sm:flex-row gap-3 items-center justify-center"
                >
                    <Link href="/register">
                        <Button size="lg" class="min-w-36"
                            >Create an account</Button
                        >
                    </Link>
                    <Link href="/login">
                        <Button variant="outline" size="lg" class="min-w-36"
                            >Sign in</Button
                        >
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="border-t py-6 px-4 text-center"
            style="border-color: hsl(240 5.9% 90%)"
        >
            <p class="text-xs" style="color: hsl(240 3.8% 46.1%)">
                &copy; {{ new Date().getFullYear() }} SSCEVS. All rights
                reserved.
            </p>
        </footer>
    </div>
</template>
