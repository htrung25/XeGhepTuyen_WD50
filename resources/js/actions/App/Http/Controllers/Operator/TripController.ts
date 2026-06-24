import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/operator/trips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::index
* @see app/Http/Controllers/Operator/TripController.php:23
* @route '/api/operator/trips'
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
* @see \App\Http\Controllers\Operator\TripController::store
* @see app/Http/Controllers/Operator/TripController.php:37
* @route '/api/operator/trips'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/operator/trips',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\TripController::store
* @see app/Http/Controllers/Operator/TripController.php:37
* @route '/api/operator/trips'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\TripController::store
* @see app/Http/Controllers/Operator/TripController.php:37
* @route '/api/operator/trips'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::store
* @see app/Http/Controllers/Operator/TripController.php:37
* @route '/api/operator/trips'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::store
* @see app/Http/Controllers/Operator/TripController.php:37
* @route '/api/operator/trips'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Operator\TripController::bulkStore
* @see app/Http/Controllers/Operator/TripController.php:58
* @route '/api/operator/trips/bulk'
*/
export const bulkStore = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkStore.url(options),
    method: 'post',
})

bulkStore.definition = {
    methods: ["post"],
    url: '/api/operator/trips/bulk',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\TripController::bulkStore
* @see app/Http/Controllers/Operator/TripController.php:58
* @route '/api/operator/trips/bulk'
*/
bulkStore.url = (options?: RouteQueryOptions) => {
    return bulkStore.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\TripController::bulkStore
* @see app/Http/Controllers/Operator/TripController.php:58
* @route '/api/operator/trips/bulk'
*/
bulkStore.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: bulkStore.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::bulkStore
* @see app/Http/Controllers/Operator/TripController.php:58
* @route '/api/operator/trips/bulk'
*/
const bulkStoreForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkStore.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::bulkStore
* @see app/Http/Controllers/Operator/TripController.php:58
* @route '/api/operator/trips/bulk'
*/
bulkStoreForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkStore.url(options),
    method: 'post',
})

bulkStore.form = bulkStoreForm

/**
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/operator/trips/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
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
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::show
* @see app/Http/Controllers/Operator/TripController.php:92
* @route '/api/operator/trips/{id}'
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
* @see \App\Http\Controllers\Operator\TripController::cancel
* @see app/Http/Controllers/Operator/TripController.php:104
* @route '/api/operator/trips/{id}/cancel'
*/
export const cancel = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/api/operator/trips/{id}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\TripController::cancel
* @see app/Http/Controllers/Operator/TripController.php:104
* @route '/api/operator/trips/{id}/cancel'
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
* @see \App\Http\Controllers\Operator\TripController::cancel
* @see app/Http/Controllers/Operator/TripController.php:104
* @route '/api/operator/trips/{id}/cancel'
*/
cancel.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::cancel
* @see app/Http/Controllers/Operator/TripController.php:104
* @route '/api/operator/trips/{id}/cancel'
*/
const cancelForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::cancel
* @see app/Http/Controllers/Operator/TripController.php:104
* @route '/api/operator/trips/{id}/cancel'
*/
cancelForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

cancel.form = cancelForm

/**
* @see \App\Http\Controllers\Operator\TripController::complete
* @see app/Http/Controllers/Operator/TripController.php:129
* @route '/api/operator/trips/{id}/complete'
*/
export const complete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

complete.definition = {
    methods: ["post"],
    url: '/api/operator/trips/{id}/complete',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\TripController::complete
* @see app/Http/Controllers/Operator/TripController.php:129
* @route '/api/operator/trips/{id}/complete'
*/
complete.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return complete.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\TripController::complete
* @see app/Http/Controllers/Operator/TripController.php:129
* @route '/api/operator/trips/{id}/complete'
*/
complete.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::complete
* @see app/Http/Controllers/Operator/TripController.php:129
* @route '/api/operator/trips/{id}/complete'
*/
const completeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\TripController::complete
* @see app/Http/Controllers/Operator/TripController.php:129
* @route '/api/operator/trips/{id}/complete'
*/
completeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: complete.url(args, options),
    method: 'post',
})

complete.form = completeForm

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
export const manifest = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manifest.url(args, options),
    method: 'get',
})

manifest.definition = {
    methods: ["get","head"],
    url: '/api/operator/trips/{id}/manifest',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
manifest.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return manifest.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
manifest.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manifest.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
manifest.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manifest.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
const manifestForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manifest.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
manifestForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manifest.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\TripController::manifest
* @see app/Http/Controllers/Operator/TripController.php:157
* @route '/api/operator/trips/{id}/manifest'
*/
manifestForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manifest.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

manifest.form = manifestForm

const TripController = { index, store, bulkStore, show, cancel, complete, manifest }

export default TripController