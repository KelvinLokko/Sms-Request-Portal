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
import { index as adminPayments } from '@/routes/admin/payments';
import { index as adminRates } from '@/routes/admin/rates';
import { index as adminSenderIds } from '@/routes/admin/sender-ids';
import { index as adminTaxRates } from '@/routes/admin/tax-rates';
import { index as campaigns } from '@/routes/campaigns';
import { index as invoices } from '@/routes/invoices';
import { index as senderIds } from '@/routes/sender-ids';
import type { NavItem } from '@/types';

const page = usePage();
const isStaff = computed(() => page.props.auth.isPlatformStaff);
const roles = computed(() => (page.props.auth.roles as string[]) ?? []);
const companyApproved = computed(
    () => page.props.auth.company?.status === 'approved',
);
const isFinance = computed(() =>
    roles.value.some((r) =>
        ['super-admin', 'admin', 'finance'].includes(r),
    ),
);
const isSupportStaff = computed(() =>
    roles.value.some((r) =>
        ['super-admin', 'admin', 'support'].includes(r),
    ),
);
const isAdmin = computed(() =>
    roles.value.some((r) => ['super-admin', 'admin'].includes(r)),
);
const canReviewCampaigns = computed(() =>
    roles.value.some((r) =>
        ['super-admin', 'admin', 'support', 'finance'].includes(r),
    ),
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (!isStaff.value) {
        items.push({
            title: 'Sender IDs',
            href: senderIds(),
            icon: Radio,
        });

        if (companyApproved.value) {
            items.push(
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
    }

    if (isStaff.value) {
        items.push({
            title: 'Analytics',
            href: adminAnalytics(),
            icon: ChartColumn,
        });

        if (isSupportStaff.value) {
            items.push(
                {
                    title: 'Companies',
                    href: adminCompanies(),
                    icon: Building2,
                },
                {
                    title: 'Sender ID review',
                    href: adminSenderIds(),
                    icon: Radio,
                },
                {
                    title: 'Fulfilment',
                    href: adminFulfilment(),
                    icon: Send,
                },
            );
        }

        if (canReviewCampaigns.value) {
            items.push({
                title: 'Campaign review',
                href: adminCampaigns(),
                icon: ClipboardCheck,
            });
        }

        if (isFinance.value) {
            items.push(
                {
                    title: 'Payments',
                    href: adminPayments(),
                    icon: Wallet,
                },
                {
                    title: 'SMS rates',
                    href: adminRates(),
                    icon: Percent,
                },
                {
                    title: 'Tax rates',
                    href: adminTaxRates(),
                    icon: Receipt,
                },
                {
                    title: 'Invoices',
                    href: invoices(),
                    icon: Receipt,
                },
            );
        }

        if (isAdmin.value) {
            items.push({
                title: 'Audit log',
                href: adminActivity(),
                icon: ScrollText,
            });
        }
    }

    return items;
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
