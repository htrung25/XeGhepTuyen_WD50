import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/operator/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::index
* @see app/Http/Controllers/Operator/NotificationController.php:13
* @route '/api/operator/notifications'
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
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
export const pendingCounts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingCounts.url(options),
    method: 'get',
})

pendingCounts.definition = {
    methods: ["get","head"],
    url: '/api/operator/pending-counts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
pendingCounts.url = (options?: RouteQueryOptions) => {
    return pendingCounts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
pendingCounts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingCounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
pendingCounts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pendingCounts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
const pendingCountsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pendingCounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
pendingCountsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pendingCounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::pendingCounts
* @see app/Http/Controllers/Operator/NotificationController.php:31
* @route '/api/operator/pending-counts'
*/
pendingCountsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pendingCounts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

pendingCounts.form = pendingCountsForm

/**
* @see \App\Http\Controllers\Operator\NotificationController::markAllRead
* @see app/Http/Controllers/Operator/NotificationController.php:62
* @route '/api/operator/notifications/read-all'
*/
export const markAllRead = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: markAllRead.url(options),
    method: 'put',
})

markAllRead.definition = {
    methods: ["put"],
    url: '/api/operator/notifications/read-all',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\NotificationController::markAllRead
* @see app/Http/Controllers/Operator/NotificationController.php:62
* @route '/api/operator/notifications/read-all'
*/
markAllRead.url = (options?: RouteQueryOptions) => {
    return markAllRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\NotificationController::markAllRead
* @see app/Http/Controllers/Operator/NotificationController.php:62
* @route '/api/operator/notifications/read-all'
*/
markAllRead.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: markAllRead.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::markAllRead
* @see app/Http/Controllers/Operator/NotificationController.php:62
* @route '/api/operator/notifications/read-all'
*/
const markAllReadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllRead.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::markAllRead
* @see app/Http/Controllers/Operator/NotificationController.php:62
* @route '/api/operator/notifications/read-all'
*/
markAllReadForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllRead.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

markAllRead.form = markAllReadForm

/**
* @see \App\Http\Controllers\Operator\NotificationController::markRead
* @see app/Http/Controllers/Operator/NotificationController.php:46
* @route '/api/operator/notifications/{id}/read'
*/
export const markRead = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: markRead.url(args, options),
    method: 'put',
})

markRead.definition = {
    methods: ["put"],
    url: '/api/operator/notifications/{id}/read',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\NotificationController::markRead
* @see app/Http/Controllers/Operator/NotificationController.php:46
* @route '/api/operator/notifications/{id}/read'
*/
markRead.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return markRead.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\NotificationController::markRead
* @see app/Http/Controllers/Operator/NotificationController.php:46
* @route '/api/operator/notifications/{id}/read'
*/
markRead.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: markRead.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::markRead
* @see app/Http/Controllers/Operator/NotificationController.php:46
* @route '/api/operator/notifications/{id}/read'
*/
const markReadForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markRead.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\NotificationController::markRead
* @see app/Http/Controllers/Operator/NotificationController.php:46
* @route '/api/operator/notifications/{id}/read'
*/
markReadForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markRead.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

markRead.form = markReadForm

const NotificationController = { index, pendingCounts, markAllRead, markRead }

export default NotificationController