<script setup lang="ts">
import { useSubmitForm } from '@/composables/useSubmitForm'

import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { DialogClose } from '@/components/ui/dialog'
import { computed, watch } from 'vue'

const props = defineProps<{
    category?: { id: number; name: string } | null
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const isEdit = computed(() => !!props.category)

const { form, submitCreate, submitUpdate } = useSubmitForm({
    initial: { name: props.category?.name ?? '' },
    onSuccess: () => emit('close'),
})

watch(
    () => props.category,
    (c) => {
        form.defaults({ name: c?.name ?? '' })
        form.reset()
    }
)

const submit = () => {
    if (isEdit.value) {
        return submitUpdate({
            routeName: 'categories.update',
            routeParams: { category: props.category!.id },
        })
    }

    return submitCreate({
        routeName: 'categories.store',
    })
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" />
            <InputError :message="form.errors.name" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="form.processing">
                {{ isEdit ? 'Update' : 'Save' }}
            </Button>

            <DialogClose as-child>
                <Button type="button" variant="outline">Cancel</Button>
            </DialogClose>
        </div>
    </form>
</template>
