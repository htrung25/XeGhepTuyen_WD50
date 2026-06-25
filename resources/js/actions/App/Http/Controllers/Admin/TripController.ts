import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/admin/trips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::index
* @see app/Http/Controllers/Admin/TripController.php:36
* @route '/api/admin/trips'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Admin\TripController::autoResolve
* @see app/Http/Controllers/Admin/TripController.php:25
* @route '/api/admin/trips/auto-resolve'
*/
export const autoResolve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: autoResolve.url(options),
    method: 'post',
})

autoResolve.definition = {
    methods: ["post"],
    url: '/api/admin/trips/auto-resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\TripController::autoResolve
* @see app/Http/Controllers/Admin/TripController.php:25
* @route '/api/admin/trips/auto-resolve'
*/
autoResolve.url = (options?: RouteQueryOptions) => {
    return autoResolve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TripController::autoResolve
* @see app/Http/Controllers/Admin/TripController.php:25
* @route '/api/admin/trips/auto-resolve'
*/
autoResolve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: autoResolve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\TripController::autoResolve
* @see app/Http/Controllers/Admin/TripController.php:25
* @route '/api/admin/trips/auto-resolve'
*/
const autoResolveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: autoResolve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\TripController::autoResolve
* @see app/Http/Controllers/Admin/TripController.php:25
* @route '/api/admin/trips/auto-resolve'
*/
autoResolveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: autoResolve.url(options),
    method: 'post',
})

autoResolve.form = autoResolveForm

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
export const monitor = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitor.url(options),
    method: 'get',
})

monitor.definition = {
    methods: ["get","head"],
    url: '/api/admin/trips/monitor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
monitor.url = (options?: RouteQueryOptions) => {
    return monitor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
monitor.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitor.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
monitor.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitor.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
const monitorForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monitor.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
monitorForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monitor.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::monitor
* @see app/Http/Controllers/Admin/TripController.php:67
* @route '/api/admin/trips/monitor'
*/
monitorForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: monitor.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

monitor.form = monitorForm

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/admin/trips/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\TripController::show
* @see app/Http/Controllers/Admin/TripController.php:53
* @route '/api/admin/trips/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Admin\TripController::cancel
* @see app/Http/Controllers/Admin/TripController.php:77
* @route '/api/admin/trips/{id}/cancel'
*/
export const cancel = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/api/admin/trips/{id}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\TripController::cancel
* @see app/Http/Controllers/Admin/TripController.php:77
* @route '/api/admin/trips/{id}/cancel'
*/
cancel.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return cancel.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\TripController::cancel
* @see app/Http/Controllers/Admin/TripController.php:77
* @route '/api/admin/trips/{id}/cancel'
*/
cancel.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\TripController::cancel
* @see app/Http/Controllers/Admin/TripController.php:77
* @route '/api/admin/trips/{id}/cancel'
*/
const cancelForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\TripController::cancel
* @see app/Http/Controllers/Admin/TripController.php:77
* @route '/api/admin/trips/{id}/cancel'
*/
cancelForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

cancel.form = cancelForm

const TripController = { index, autoResolve, monitor, show, cancel }

export default TripController