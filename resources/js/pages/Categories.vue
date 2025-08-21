<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import CategoryForm from '@/components/CategoryForm.vue'
import PaginationNav from '@/components/PaginationNav.vue'
import { ref } from 'vue'
import { Trash, Pencil } from 'lucide-vue-next'
import { type BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Categories', href: '/categories' }]

const open = ref(false)
const editingCategory = ref<null | { id: number; name: string }>(null)

const props = defineProps<{ categories: any }>()

function openCreate() {
    editingCategory.value = null
    open.value = true
}
function openEdit(cat: { id: number; name: string }) {
    editingCategory.value = cat
    open.value = true
}
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="space-y-6">
                <Dialog v-model:open="open">
                    <div class="flex justify-end">
                        <DialogTrigger as-child>
                            <Button type="button" variant="outline" @click="openCreate">
                                Create Category
                            </Button>
                        </DialogTrigger>
                    </div>

                    <div class="overflow-x-auto mt-2">
                        <table class="min-w-full divide-y divide-neutral-200 text-sm">
                            <thead class="bg-neutral-50">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left font-semibold">Name</th>
                                <th scope="col" class="px-3 py-2"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200">
                            <tr v-for="cat in props.categories.data" :key="cat.id">
                                <td class="px-3 py-2">{{ cat.name }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex justify-end gap-2">
                                        <DialogTrigger as-child>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                class="flex items-center gap-1"
                                                @click="openEdit(cat)"
                                            >
                                                <Pencil class="w-4 h-4" />
                                                Edit
                                            </Button>
                                        </DialogTrigger>

                                        <Button
                                            as-child
                                            variant="destructive"
                                            size="sm"
                                            class="flex items-center gap-1"
                                        >
                                            <Link
                                                as="button"
                                                type="button"
                                                method="delete"
                                                :href="route('categories.destroy', cat.id)"
                                                preserve-scroll
                                            >
                                                <Trash class="w-4 h-4" />
                                                Delete
                                            </Link>
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <DialogContent class="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle>
                                {{ editingCategory ? 'Edit category' : 'Create category' }}
                            </DialogTitle>
                            <DialogDescription class="sr-only">
                                {{ editingCategory
                                ? 'Update the name of this category.'
                                : 'Add a name for your new category.' }}
                            </DialogDescription>
                        </DialogHeader>

                        <CategoryForm
                            :category="editingCategory"
                            in-modal
                            @close="open = false; editingCategory = null"
                        />
                    </DialogContent>
                </Dialog>

                <PaginationNav class="mt-4" :links="props.categories.links" />
            </div>
        </div>
    </AppLayout>
</template>
