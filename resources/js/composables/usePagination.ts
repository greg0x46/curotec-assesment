import { reactive, toRefs } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

type Filters = Record<string, string | number | boolean | null | undefined>

type VisitOpts = {
    preserveScroll?: boolean
    preserveState?: boolean
    replace?: boolean
}

const defaultVisit: VisitOpts = {
    preserveScroll: true,
    preserveState: true,
    replace: true,
}

function buildUrl(pathname: string, params: URLSearchParams) {
    const q = params.toString()
    return q ? `${pathname}?${q}` : pathname
}

export function usePagination(
    initial: Filters = {},
    visitOpts: VisitOpts = defaultVisit,
) {
    const pageCtx = usePage()

    const pathname = (() => {
        const current = pageCtx.url || window.location.pathname
        try {
            const u = new URL(current, window.location.origin)
            return u.pathname
        } catch {
            return window.location.pathname
        }
    })()

    const current = new URLSearchParams(
        (() => {
            try {
                const u = new URL(pageCtx.url, window.location.origin)
                return u.search
            } catch {
                return window.location.search
            }
        })(),
    )

    const state = reactive<Filters>({ ...initial })
    Object.keys(state).forEach((k) => {
        const v = current.get(k)
        if (v !== null) state[k] = v
    })

    const visitWith = (params: URLSearchParams) => {
        router.visit(buildUrl(pathname, params), visitOpts)
    }

    const setFilter = (key: string, value: Filters[string]) => {
        const params = new URLSearchParams()
        Object.entries(state).forEach(([k, v]) => {
            if (k === key) v = value
            if (v !== null && v !== undefined && v !== '' && v !== false)
                params.set(k, String(v))
        })
        params.set('page', '1')
        visitWith(params)
    }

    const goToPage = (page: number) => {
        const params = new URLSearchParams()
        Object.entries(state).forEach(([k, v]) => {
            if (v !== null && v !== undefined && v !== '' && v !== false)
                params.set(k, String(v))
        })
        params.set('page', String(page))
        visitWith(params)
    }

    return {
        ...toRefs(state),
        setFilter,
        goToPage,
    }
}
