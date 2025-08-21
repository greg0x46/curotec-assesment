import { useForm } from '@inertiajs/vue3'
import type {
    Page,
    Errors,
    FormDataType,
    VisitOptions,
    PreserveStateOption,
} from '@inertiajs/core'

type CommonOpts<T extends FormDataType<T>> = {
    initial: T
    preserveScroll?: boolean | ((page: Page) => boolean)
    preserveState?: PreserveStateOption
    resetOnSuccess?: boolean
    onSuccess?: () => void
    onError?: (errors: Errors) => void
    onFinish?: () => void
}

type Target = {
    url?: string
    routeName?: string
    routeParams?: Record<string, unknown>
}

export function useSubmitForm<T extends FormDataType<T>>(opts: CommonOpts<T>) {
    const form = useForm<T>(opts.initial)

    type SubmitOptions = VisitOptions

    const doSubmit = async (
        method: 'post' | 'put' | 'patch' | 'delete',
        target: Target
    ): Promise<void> => {
        const url =
            target.url ??
            (target.routeName ? route(target.routeName, target.routeParams) : undefined)

        if (!url) throw new Error('You must provide url or routeName.')

        const options: SubmitOptions = {
            preserveScroll: opts.preserveScroll ?? true,
            preserveState: opts.preserveState ?? true,
            onSuccess: () => {
                if (opts.resetOnSuccess ?? true) form.reset()
                opts.onSuccess?.()
            },
            onError: (errors: Errors) => opts.onError?.(errors),
            onFinish: () => opts.onFinish?.(),
        }

        if (method === 'post')   return await form.post(url, options)
        if (method === 'put')    return await form.put(url, options)
        if (method === 'patch')  return await form.patch(url, options)
        return await form.delete(url, options)
    }

    const submitCreate = (target: Target) => doSubmit('post', target)
    const submitUpdate = (target: Target) => doSubmit('put', target)
    const submit       = doSubmit

    return { form, submitCreate, submitUpdate, submit }
}
