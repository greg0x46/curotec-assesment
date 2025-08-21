<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

type LinkItem = { url: string | null; label: string; active: boolean }

const props = defineProps<{
    links?: LinkItem[]
    currentPage?: number
    lastPage?: number
    label?: string
    small?: boolean
}>()

const emit = defineEmits<{
    (e: 'navigate', payload: { page: number }): void
}>()

const size = computed(() => (props.small ? 'h-8 px-2 text-xs' : 'h-9 px-3 text-sm'))

const hasServerLinks = computed(() => !!props.links && props.links.length > 0)

const canPrev = computed(() =>
    hasServerLinks.value
        ? !!props.links?.find((l) => l.active)?.url
        : (props.currentPage ?? 1) > 1
)

const canNext = computed(() =>
    hasServerLinks.value
        ? !!props.links && props.links.slice().reverse().find((l) => l.active)?.url
        : (props.currentPage ?? 1) < (props.lastPage ?? 1)
)

const prevPage = () => {
    if (!hasServerLinks.value && props.currentPage && props.currentPage > 1) {
        emit('navigate', { page: props.currentPage - 1 })
    }
}

const nextPage = () => {
    if (!hasServerLinks.value && props.currentPage && props.lastPage && props.currentPage < props.lastPage) {
        emit('navigate', { page: props.currentPage + 1 })
    }
}
</script>

<template>
    <nav
        class="flex items-center gap-2"
        :aria-label="label ?? 'Pagination'"
    >
        <button
            v-if="!hasServerLinks"
            type="button"
            class="rounded border px-2 py-1 disabled:opacity-40"
            :class="size"
            :disabled="!canPrev"
            @click="prevPage"
        >
            Prev
        </button>

        <template v-if="hasServerLinks">
            <template v-for="(link, i) in links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="rounded px-2 py-1"
                    :class="[size, link.active ? 'font-semibold underline' : 'text-gray-600 hover:text-black']"
                >
                    <span v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="px-2 py-1 text-gray-300"
                    :class="size"
                    v-html="link.label"
                />
            </template>
        </template>

        <span v-else class="text-sm">
            Page {{ currentPage }} of {{ lastPage }}
        </span>

        <button
            v-if="!hasServerLinks"
            type="button"
            class="rounded border px-2 py-1 disabled:opacity-40"
            :class="size"
            :disabled="!canNext"
            @click="nextPage"
        >
            Next
        </button>
    </nav>
</template>
