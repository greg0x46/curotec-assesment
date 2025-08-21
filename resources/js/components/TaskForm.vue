<script setup lang="ts">
import { computed, watch } from 'vue'
import { useSubmitForm } from '@/composables/useSubmitForm'

import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { DialogClose } from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'

type Category = { id: number; name: string }
type Task = {
    id: number
    title: string
    description?: string | null
    priority: string | number
    status: string | number
    due_date?: string | null
    categories?: Category[]
}

const props = defineProps<{
    task?: Task | null
    categories: Category[]
}>()

const emit = defineEmits<{ (e: 'close'): void }>()

const isEdit = computed(() => !!props.task)

/** Converte ISO/Date para valor aceito pelo input datetime-local (YYYY-MM-DDTHH:mm) */
function toDatetimeLocal(value?: string | null): string {
    if (!value) return ''
    try {
        const d = new Date(value)
        const pad = (n: number) => String(n).padStart(2, '0')
        const yyyy = d.getFullYear()
        const mm = pad(d.getMonth() + 1)
        const dd = pad(d.getDate())
        const hh = pad(d.getHours())
        const mi = pad(d.getMinutes())
        return `${yyyy}-${mm}-${dd}T${hh}:${mi}`
    } catch {
        // Se já vier no formato correto, retorna como está
        return String(value)
    }
}

const { form, submitCreate, submitUpdate } = useSubmitForm({
    initial: {
        title: props.task?.title ?? '',
        description: props.task?.description ?? '',
        // Ajuste os valores conforme seus enums no backend
        priority: (props.task?.priority ?? 'normal') as any,
        status: (props.task?.status ?? 'pending') as any,
        due_date: toDatetimeLocal(props.task?.due_date) as any,
        categories: (props.task?.categories ?? []).map(c => c.id) as any, // array de IDs
    },
    onSuccess: () => emit('close'),
})

watch(
    () => props.task,
    (t) => {
        form.defaults({
            title: t?.title ?? '',
            description: t?.description ?? '',
            priority: (t?.priority ?? 'normal') as any,
            status: (t?.status ?? 'pending') as any,
            due_date: toDatetimeLocal(t?.due_date) as any,
            categories: (t?.categories ?? []).map(c => c.id) as any,
        })
        form.reset()
    }
)

const submit = () => {
    if (isEdit.value) {
        return submitUpdate({
            routeName: 'tasks.update',
            routeParams: { task: props.task!.id },
        })
    }
    return submitCreate({
        routeName: 'tasks.store',
    })
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid gap-2">
            <Label for="title">Title</Label>
            <Input id="title" v-model="form.title" />
            <InputError :message="form.errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <Textarea id="description" v-model="form.description" rows="3" />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="priority">Priority</Label>
                <select
                    id="priority"
                    v-model="form.priority"
                    class="h-9 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                >
                    <!-- Ajuste os values conforme seus enums/backed enums -->
                    <option value="low">Low</option>
                    <option value="normal">Medium</option>
                    <option value="high">High</option>
                </select>
                <InputError :message="form.errors.priority" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    v-model="form.status"
                    class="h-9 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                >
                    <option value="pending">Pending</option>
                    <option value="in_progress">In progress</option>
                    <option value="done">Done</option>
                </select>
                <InputError :message="form.errors.status" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="due_date">Due date</Label>
            <Input id="due_date" type="datetime-local" v-model="form.due_date" />
            <InputError :message="form.errors.due_date" />
        </div>

        <div class="grid gap-2">
            <Label for="categories">Categories</Label>
            <select
                id="categories"
                v-model="form.categories"
                multiple
                class="min-h-28 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm"
            >
                <option v-for="c in categories" :key="c.id" :value="c.id">
                    {{ c.name }}
                </option>
            </select>
            <p class="text-xs text-neutral-500">Hold Ctrl/Cmd to select multiple.</p>
            <InputError :message="form.errors.categories" />
        </div>

        <div class="flex items-center justify-end gap-3">
            <DialogClose as-child>
                <Button type="button" variant="outline">Cancel</Button>
            </DialogClose>
            <Button type="submit" :disabled="form.processing">
                {{ isEdit ? 'Update' : 'Save' }}
            </Button>
        </div>
    </form>
</template>
