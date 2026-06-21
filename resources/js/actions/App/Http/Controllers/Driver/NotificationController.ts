import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/driver/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\NotificationController::index
* @see app/Http/Controllers/Driver/NotificationController.php:12
* @route '/api/driver/notifications'
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
* @see \App\Http\Controllers\Driver\NotificationController::markRead
* @see app/Http/Controllers/Driver/NotificationController.php:28
* @route '/api/driver/notifications/{id}/read'
*/
export const markRead = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: markRead.url(args, options),
    method: 'put',
})

markRead.definition = {
    methods: ["put"],
    url: '/api/driver/notifications/{id}/read',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Driver\NotificationController::markRead
* @see app/Http/Controllers/Driver/NotificationController.php:28
* @route '/api/driver/notifications/{id}/read'
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
* @see \App\Http\Controllers\Driver\NotificationController::markRead
* @see app/Http/Controllers/Driver/NotificationController.php:28
* @route '/api/driver/notifications/{id}/read'
*/
markRead.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: markRead.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Driver\NotificationController::markRead
* @see app/Http/Controllers/Driver/NotificationController.php:28
* @route '/api/driver/notifications/{id}/read'
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
* @see \App\Http\Controllers\Driver\NotificationController::markRead
* @see app/Http/Controllers/Driver/NotificationController.php:28
* @route '/api/driver/notifications/{id}/read'
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

const NotificationController = { index, markRead }

export default NotificationController