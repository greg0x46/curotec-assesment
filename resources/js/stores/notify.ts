import { defineStore } from 'pinia'
import { toast, type ExternalToast } from 'vue-sonner'

type ToastType = 'success' | 'error' | 'info' | 'warning'

// Reuse vue-sonner's method signature
type ToastFn = (
    message: Parameters<typeof toast.success>[0],
    data?: Parameters<typeof toast.success>[1]
) => ReturnType<typeof toast.success>

export type NotifyOptions = {
    title?: string
    description?: string
    duration?: number
    type?: ToastType
    action?: ExternalToast['action']
}

export const useNotify = defineStore('notify', () => {
    const base = (opts: NotifyOptions, fallbackTitle: string, fn: ToastFn) =>
        fn(opts.title ?? fallbackTitle, {
            description: opts.description,
            duration: opts.duration,
            action: opts.action,
        } as ExternalToast)

    const show = (opts: NotifyOptions) => {
        switch (opts.type) {
            case 'success':
                return base(opts, 'Success', toast.success)
            case 'error':
                return base(opts, 'Error', toast.error)
            case 'warning':
                return base(opts, 'Warning', toast.warning)
            case 'info':
            default:
                // `toast` is callable; narrow it to the method signature
                return base(opts, 'Notice', toast as unknown as ToastFn)
        }
    }

    const success = (title: string, description?: string, duration?: number) =>
        toast.success(title, { description, duration })

    const error = (title: string, description?: string, duration?: number) =>
        toast.error(title, { description, duration })

    const info = (title: string, description?: string, duration?: number) =>
        toast(title, { description, duration })

    const warning = (title: string, description?: string, duration?: number) =>
        toast.warning(title, { description, duration })

    return { show, success, error, info, warning }
})
