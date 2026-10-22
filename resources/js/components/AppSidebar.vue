<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ClipboardList,
    CreditCard,
    FileStack,
    Gauge,
    KeyRound,
    LayoutGrid,
    LayoutList,
    Settings2,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import { index as apiTokens } from '@/routes/api-tokens';
import { index as myRequests } from '@/routes/requests';
import { index as services } from '@/routes/services';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth.isAdmin);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Services',
        href: services(),
        icon: LayoutList,
        matchPrefix: true,
    },
    {
        title: 'My applications',
        href: myRequests(),
        icon: ClipboardList,
        matchPrefix: true,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Overview',
        href: admin.dashboard(),
        icon: Gauge,
    },
    {
        title: 'Requests',
        href: admin.requests.index(),
        icon: FileStack,
        matchPrefix: true,
    },
    {
        title: 'Manage services',
        href: admin.services.index(),
        icon: Settings2,
        matchPrefix: true,
    },
    {
        title: 'Payments',
        href: admin.payments.index(),
        icon: CreditCard,
    },
];

const developerNavItems: NavItem[] = [
    {
        title: 'API tokens',
        href: apiTokens(),
        icon: KeyRound,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Applicant" />
            <NavMain
                v-if="isAdmin"
                :items="adminNavItems"
                label="Back office"
            />
            <NavMain :items="developerNavItems" label="Developer" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
