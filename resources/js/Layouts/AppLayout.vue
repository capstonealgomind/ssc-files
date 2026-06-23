<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Notifications from '@/Components/Notifications.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);

function toggleSidebar() {
    if (window.innerWidth >= 1024) {
        sidebarCollapsed.value = !sidebarCollapsed.value;
    } else {
        sidebarOpen.value = !sidebarOpen.value;
    }
}

const navItems = computed(() => {
    const items = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM13 7a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2V7zM3 15a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2zM13 15a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2z" /></svg>`,
        },
    ];

    if (user.value?.role === 'admin') {
        items.push(
            {
                title: 'Elections',
                href: '/elections',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
            },
            {
                title: 'Candidates',
                href: '/candidates',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>`,
            },
            {
                title: 'Voters',
                href: '/voters',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>`,
            },
            {
                title: 'Monitoring',
                href: '/monitoring',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>`,
            },
            {
                title: 'Reports',
                href: '/reports',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>`,
            },
            {
                title: 'Accounts',
                href: '/accounts',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>`,
            },
            {
                title: 'Settings',
                href: '/settings',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`,
            },
        );
    }

    return items;
});

function isActive(href) {
    return page.url === href || page.url.startsWith(href + '/');
}

function logout() {
    router.post('/logout');
}

function getInitials(name) {
    if (!name) return '?';
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}
</script>

<template>
    <div class="flex h-screen overflow-hidden" style="background-color: hsl(240 4.8% 95.9%);">

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 lg:hidden"
            style="background-color: rgba(0,0,0,0.4);"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex flex-col border-r shrink-0 transition-all duration-300 ease-in-out overflow-hidden',
                'lg:static lg:inset-auto',
                // Mobile: full width drawer, slide in/out
                sidebarOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full',
                // Desktop: full or icon-only
                sidebarCollapsed ? 'lg:w-16 lg:translate-x-0' : 'lg:w-64 lg:translate-x-0',
            ]"
            style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);"
        >
            <!-- Logo -->
            <div class="h-14 flex items-center border-b shrink-0 overflow-hidden" style="border-color: hsl(240 5.9% 90%);">
                <Link
                    href="/dashboard"
                    class="flex items-center gap-2 transition-all duration-300"
                    :class="sidebarCollapsed ? 'px-4 justify-center w-full' : 'px-4'"
                >
                    <div class="h-7 w-7 rounded-md flex items-center justify-center shrink-0" style="background-color: hsl(240 5.9% 10%);">
                        <svg class="h-4 w-4" style="color: hsl(0 0% 98%);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span
                        class="font-semibold text-sm whitespace-nowrap transition-all duration-300 overflow-hidden"
                        :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'"
                        style="color: hsl(240 10% 3.9%);"
                    >
                        SSCEVS
                    </span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto overflow-x-hidden">
                <!-- Section label — hidden when collapsed -->
                <p
                    class="mb-2 text-xs font-medium uppercase tracking-wider whitespace-nowrap transition-all duration-300 overflow-hidden"
                    :class="sidebarCollapsed ? 'px-0 h-0 opacity-0 mb-0' : 'px-2 h-auto opacity-100'"
                    style="color: hsl(240 3.8% 46.1%);"
                >
                    Platform
                </p>

                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 py-2 text-sm font-medium rounded-md transition-colors relative group"
                    :class="[
                        isActive(item.href) ? '' : 'hover:bg-gray-100',
                        sidebarCollapsed ? 'px-3 justify-center' : 'px-3',
                    ]"
                    :style="isActive(item.href)
                        ? 'background-color: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);'
                        : 'color: hsl(240 3.8% 46.1%);'"
                >
                    <span class="h-4 w-4 shrink-0" v-html="item.icon" />

                    <!-- Label — fades out when collapsed -->
                    <span
                        class="whitespace-nowrap transition-all duration-300 overflow-hidden"
                        :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'"
                    >
                        {{ item.title }}
                    </span>

                    <!-- Tooltip on collapsed -->
                    <span
                        v-if="sidebarCollapsed"
                        class="absolute left-full ml-2 px-2 py-1 text-xs font-medium rounded-md whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-md"
                        style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                    >
                        {{ item.title }}
                    </span>
                </Link>
            </nav>

            <!-- User info at bottom -->
            <div class="border-t p-2 shrink-0" style="border-color: hsl(240 5.9% 90%);">
                <div
                    class="flex items-center gap-3 py-2 rounded-md overflow-hidden transition-all duration-300 relative group"
                    :class="sidebarCollapsed ? 'px-1 justify-center' : 'px-2'"
                    style="background-color: hsl(240 4.8% 95.9%);"
                >
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0" style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">
                        {{ getInitials(user?.name) }}
                    </div>
                    <!-- User name + role — hidden when collapsed -->
                    <div
                        class="min-w-0 transition-all duration-300 overflow-hidden"
                        :class="sidebarCollapsed ? 'w-0 opacity-0' : 'flex-1 opacity-100'"
                    >
                        <p class="text-sm font-medium truncate" style="color: hsl(240 10% 3.9%);">{{ user?.name }}</p>
                        <p class="text-xs truncate capitalize" style="color: hsl(240 3.8% 46.1%);">{{ user?.role }}</p>
                    </div>

                    <!-- Tooltip on collapsed -->
                    <span
                        v-if="sidebarCollapsed"
                        class="absolute left-full ml-2 px-2 py-1.5 text-xs font-medium rounded-md whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity z-50 shadow-md leading-snug"
                        style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);"
                    >
                        {{ user?.name }}<br>
                        <span class="opacity-70 capitalize">{{ user?.role }}</span>
                    </span>
                </div>
            </div>
        </aside>

        <!-- Main content area -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Top header -->
            <header class="h-14 border-b flex items-center px-4 gap-3 shrink-0" style="background-color: hsl(0 0% 100%); border-color: hsl(240 5.9% 90%);">

                <!-- Toggle button -->
                <button
                    class="p-2 rounded-md transition-colors hover:bg-gray-100 flex items-center justify-center shrink-0"
                    style="color: hsl(240 3.8% 46.1%);"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    @click="toggleSidebar"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title -->
                <div class="flex-1">
                    <slot name="header" />
                </div>

                <!-- Right side actions -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0" style="background-color: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">
                            {{ getInitials(user?.name) }}
                        </div>
                        <span class="hidden sm:block text-sm font-medium" style="color: hsl(240 10% 3.9%);">{{ user?.name }}</span>
                    </div>
                    <button
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border transition-colors hover:bg-gray-50"
                        style="border-color: hsl(240 5.9% 90%); color: hsl(240 3.8% 46.1%);"
                        @click="logout"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log out
                    </button>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4">
                <slot />
            </main>
        </div>

        <Notifications />
    </div>
</template>
