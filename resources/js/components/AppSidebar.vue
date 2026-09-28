<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    GraduationCap,
    LayoutGrid,
    Library,
    LineChart,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
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
import { useAuth } from '@/composables/useAuth';
import { dashboard } from '@/routes';
import { index as adminStudentProgress } from '@/routes/admin/student-progress';
import { index as adminUsers } from '@/routes/admin/users';
import { index as adminWordSets } from '@/routes/admin/word-sets';
import { index as parentAnalytics } from '@/routes/parent/analytics';
import { index as studentReviews } from '@/routes/student/reviews';
import { index as studentWordSets } from '@/routes/student/word-sets';
import type { NavItem } from '@/types';

const { t } = useI18n();
const { hasRole } = useAuth();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: t('nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (hasRole('student')) {
        items.push(
            {
                title: t('nav.reviews'),
                href: studentReviews(),
                icon: BookOpen,
            },
            {
                title: t('nav.myWordSets'),
                href: studentWordSets(),
                icon: Library,
            },
        );
    }

    if (hasRole('parent')) {
        items.push({
            title: t('nav.children'),
            href: parentAnalytics(),
            icon: LineChart,
        });
    }

    if (hasRole('admin')) {
        items.push(
            {
                title: t('nav.users'),
                href: adminUsers(),
                icon: Users,
            },
            {
                title: t('nav.studentProgress'),
                href: adminStudentProgress(),
                icon: GraduationCap,
            },
            {
                title: t('nav.dictionaries'),
                href: adminWordSets(),
                icon: Library,
            },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [];
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
