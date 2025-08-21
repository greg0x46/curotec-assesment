<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import AppToaster from '@/components/AppToaster.vue'
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3'
import { useEcho } from '@laravel/echo-vue'
import { useNotify } from '@/stores/notify'

const page = usePage()
const userId = page.props.auth.user.id
const notify = useNotify()

useEcho(
    `tasks.user.${userId}`,
    '.TaskUpdated',
    (e: { id: number; title: string; status: string, action: string }) => {
        notify.success(`Task ${e.action}`, `${e.title} → ${e.status}`)
    },
    { type: 'private' }
)

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <AppToaster />
        <slot />
    </AppLayout>
</template>
