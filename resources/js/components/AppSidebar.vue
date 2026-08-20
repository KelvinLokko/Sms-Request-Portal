<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    ChartColumn,
    ClipboardCheck,
    LayoutGrid,
    Megaphone,
    Percent,
    Radio,
    Receipt,
    ScrollText,
    Send,
    Shield,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { index as adminActivity } from '@/routes/admin/activity';
import { index as adminAnalytics } from '@/routes/admin/analytics';
import { index as adminCampaigns } from '@/routes/admin/campaigns';
import { index as adminCompanies } from '@/routes/admin/companies';
import { index as adminFulfilment } from '@/routes/admin/fulfilment';
import { index as adminPayments, report as adminPaymentReport } from '@/routes/admin/payments';
import { index as adminRates } from '@/routes/admin/rates';
import { index as adminRoles } from '@/routes/admin/roles';
import { index as adminSenderIds } from '@/routes/admin/sender-ids';
import { index as adminTaxRates } from '@/routes/admin/tax-rates';
import { index as adminUsers } from '@/routes/admin/users';
import { index as campaigns } from '@/routes/campaigns';
import { index as invoices } from '@/routes/invoices';
import { index as senderIds } from '@/routes/sender-ids';
import type { NavGroup, NavItem } from '@/types';

const page = usePage();
const isStaff = computed(() => page.props.auth.isPlatformStaff);
const permissions = computed(
    () => (page.props.auth.permissions as string[]) ?? [],
);
const companyApproved = computed(
    () => page.props.auth.company?.status === 'approved',
);

function can(...slugs: string[]): boolean {
    return slugs.some((slug) => permissions.value.includes(slug));
}

const navGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [];

    if (!isStaff.value) {
        const workspace: NavItem[] = [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Sender IDs',
                href: senderIds(),
                icon: Radio,
            },
        ];

        if (companyApproved.value) {
            workspace.push(
                {
                    title: 'Campaigns',
                    href: campaigns(),
                    icon: Megaphone,
                },
                {
                    title: 'Invoices',
                    href: invoices(),
                    icon: Receipt,
                },
            );
        }

        groups.push({ title: 'Workspace', items: workspace });

        return groups;
    }

    const overview: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (can('admin.access')) {
        overview.push({
            title: 'Analytics',
            href: adminAnalytics(),
            icon: ChartColumn,
        });
    }

    groups.push({ title: 'Overview', items: overview });

    const operations: NavItem[] = [];

    if (can('companies.view', 'companies.manage')) {
        operations.push({
            title: 'Companies',
            href: adminCompanies(),
            icon: Building2,
        });
    }
    if (can('sender-ids.review')) {
        operations.push({
            title: 'Sender ID review',
            href: adminSenderIds(),
            icon: Radio,
        });
    }
    if (can('campaigns.fulfil')) {
        operations.push({
            title: 'Fulfilment',
            href: adminFulfilment(),
            icon: Send,
        });
    }
    if (can('admin.access')) {
        operations.push({
            title: 'Campaigns',
            href: adminCampaigns(),
            icon: ClipboardCheck,
        });
    }

    if (operations.length > 0) {
        groups.push({ title: 'Operations', items: operations });
    }

    const finance: NavItem[] = [];

    if (can('payments.manage')) {
        finance.push(
            {
                title: 'Payments',
                href: adminPayments(),
                icon: Wallet,
            },
            {
                title: 'Payment history',
                href: adminPaymentReport(),
                icon: Receipt,
            },
        );
    }
    if (can('rates.manage')) {
        finance.push({
            title: 'SMS rates',
            href: adminRates(),
            icon: Percent,
        });
    }
    if (can('tax-rates.manage')) {
        finance.push({
            title: 'Tax rates',
            href: adminTaxRates(),
            icon: Receipt,
        });
    }
    if (can('admin.access')) {
        finance.push({
            title: 'Invoices',
            href: invoices(),
            icon: Receipt,
        });
    }

    if (finance.length > 0) {
        groups.push({ title: 'Finance', items: finance });
    }

    const administration: NavItem[] = [];

    if (can('users.manage')) {
        administration.push({
            title: 'Users',
            href: adminUsers(),
            icon: Users,
        });
    }
    if (can('roles.manage')) {
        administration.push({
            title: 'Roles',
            href: adminRoles(),
            icon: Shield,
        });
    }
    if (can('activity.view')) {
        administration.push({
            title: 'Audit log',
            href: adminActivity(),
            icon: ScrollText,
        });
    }

    if (administration.length > 0) {
        groups.push({ title: 'Administration', items: administration });
    }

    return groups;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-sidebar-border/80">
        <SidebarHeader class="border-b border-sidebar-border/60 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-sidebar-accent/80">
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
