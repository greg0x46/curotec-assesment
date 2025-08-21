<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import PaginationNav from '@/components/PaginationNav.vue'
import TaskForm from '@/components/TaskForm.vue'
import { ref, watch } from 'vue'
import { Trash, Pencil, Filter as FilterIcon } from 'lucide-vue-next'
import type { BreadcrumbItem } from '@/types'

interface Category {
    id: number
    name: string
}

interface TaskRow {
    id: number
    title: string
    description?: string | null
    priority: number | string
    status: number | string
    due_date?: string | null
    assigned_to_id?: number | null
    owner_id?: number | null
    categories?: Category[]
}

const props = defineProps<{
    tasks: { data: TaskRow[]; links: any[] }
    categories: Category[]
    filters: Record<string, string>
}>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tasks', href: route('tasks.index') }]

const open = ref(false)
const editingTask = ref<null | TaskRow>(null)

function openCreate() {
    editingTask.value = null
    open.value = true
}
function openEdit(task: TaskRow) {
    editingTask.value = task
    open.value = true
}

// seed with incoming filters to avoid undefined
const form = useForm({
    q: props.filters.q || '',
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    category: props.filters.category || '',
})

const search = () => {
    form.get(route('tasks.index'), {
        preserveScroll: true,
        preserveState: true,
    })
}

// auto-apply: debounce for q, immediate for selects
let qTimer: ReturnType<typeof setTimeout> | null = null
watch(
    () => form.q,
    () => {
        if (qTimer) clearTimeout(qTimer)
        qTimer = setTimeout(() => search(), 400)
    }
)
watch([() => form.status, () => form.priority, () => form.category], () => {
    // reset page when changing filters other than q (if you pass ?page in URL)
    // form.page && (form.page = '')
    search()
})

const priorityLabel = (p: number | string) => {
    const map: Record<string, string> = {
        low: 'Low',
        normal: 'Medium',
        high: 'High',
        1: 'Low',
        2: 'Medium',
        3: 'High',
    }
    return map[String(p)] ?? String(p)
}
const statusLabel = (s: number | string) => {
    const map: Record<string, string> = {
        pending: 'Pending',
        in_progress: 'In progress',
        done: 'Done',
        0: 'Pending',
        1: 'In progress',
        2: 'Done',
    }
    return map[String(s)] ?? String(s)
}

const fmtDate = (iso?: string | null) => {
    if (!iso) return '—'
    try {
        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(iso))
    } catch {
        return iso
    }
}
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Top-right create button -->
            <div class="flex justify-end">
                <Dialog v-model:open="open">
                    <DialogTrigger as-child>
                        <Button type="button" variant="outline" @click="openCreate">
                            Create Task
                        </Button>
                    </DialogTrigger>

                    <DialogContent class="sm:max-w-[560px]">
                        <DialogHeader>
                            <DialogTitle>{{ editingTask ? 'Edit task' : 'Create task' }}</DialogTitle>
                            <DialogDescription class="sr-only">
                                {{ editingTask ? 'Update this task.' : 'Create a new task.' }}
                            </DialogDescription>
                        </DialogHeader>

                        <TaskForm
                            :task="editingTask"
                            :categories="categories"
                            in-modal
                            @close="open = false; editingTask = null"
                        />
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Filters box -->
            <div class="rounded-xl border border-neutral-200 bg-white">
                <div class="flex items-center gap-2 border-b border-neutral-200 px-4 py-3">
                    <FilterIcon class="h-4 w-4" />
                    <h2 class="text-sm font-semibold">Filters</h2>
                </div>

                <div class="grid items-end gap-4 px-4 py-4 sm:grid-cols-2 md:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="q">Search</Label>
                        <Input id="q" v-model="form.q" placeholder="Title or description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="h-9 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                        >
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In progress</option>
                            <option value="done">Done</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="priority">Priority</Label>
                        <select
                            id="priority"
                            v-model="form.priority"
                            class="h-9 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                        >
                            <option value="">All</option>
                            <option value="low">Low</option>
                            <option value="normal">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">Category</Label>
                        <!-- Ideally this should be a lazy-loaded, searchable categories dropdown to avoid loading all options at once. -->
                        <select
                            id="category"
                            v-model="form.category"
                            class="h-9 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                        >
                            <option value="">All</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm">
                    <thead class="bg-neutral-50">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left font-semibold">Title</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold">Priority</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold">Status</th>
                        <th scope="col" class="px-3 py-2 text-left font-semibold">Due date</th>
                        <th scope="col" class="px-3 py-2"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200">
                    <tr v-for="t in props.tasks.data" :key="t.id">
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ t.title }}</div>
                            <div v-if="t.categories?.length" class="text-xs text-neutral-500">
                                {{ t.categories.map(c => c.name).join(', ') }}
                            </div>
                        </td>
                        <td class="px-3 py-2">{{ priorityLabel(t.priority) }}</td>
                        <td class="px-3 py-2">{{ statusLabel(t.status) }}</td>
                        <td class="px-3 py-2">{{ fmtDate(t.due_date) }}</td>
                        <td class="px-3 py-2">
                            <div class="flex justify-end gap-2">
                                <Dialog>
                                    <DialogTrigger as-child>
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            class="flex items-center gap-1"
                                            @click="openEdit(t)"
                                        >
                                            <Pencil class="w-4 h-4" />
                                            Edit
                                        </Button>
                                    </DialogTrigger>
                                </Dialog>

                                <Button as-child variant="destructive" size="sm" class="flex items-center gap-1">
                                    <Link
                                        as="button"
                                        type="button"
                                        method="delete"
                                        :href="route('tasks.destroy', t.id)"
                                        preserve-scroll
                                    >
                                        <Trash class="w-4 h-4" />
                                        Delete
                                    </Link>
                                </Button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!props.tasks.data.length">
                        <td class="px-3 py-6 text-center text-neutral-500" colspan="5">
                            No tasks found.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <PaginationNav class="mt-4" :links="props.tasks.links" />
        </div>
    </AppLayout>
</template>
