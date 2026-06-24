import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/operator/vehicles',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::index
* @see app/Http/Controllers/Operator/VehicleController.php:16
* @route '/api/operator/vehicles'
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
* @see \App\Http\Controllers\Operator\VehicleController::store
* @see app/Http/Controllers/Operator/VehicleController.php:28
* @route '/api/operator/vehicles'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/operator/vehicles',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\VehicleController::store
* @see app/Http/Controllers/Operator/VehicleController.php:28
* @route '/api/operator/vehicles'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\VehicleController::store
* @see app/Http/Controllers/Operator/VehicleController.php:28
* @route '/api/operator/vehicles'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::store
* @see app/Http/Controllers/Operator/VehicleController.php:28
* @route '/api/operator/vehicles'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::store
* @see app/Http/Controllers/Operator/VehicleController.php:28
* @route '/api/operator/vehicles'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/operator/vehicles/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
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
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::show
* @see app/Http/Controllers/Operator/VehicleController.php:84
* @route '/api/operator/vehicles/{id}'
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
* @see \App\Http\Controllers\Operator\VehicleController::update
* @see app/Http/Controllers/Operator/VehicleController.php:96
* @route '/api/operator/vehicles/{id}'
*/
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/api/operator/vehicles/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\VehicleController::update
* @see app/Http/Controllers/Operator/VehicleController.php:96
* @route '/api/operator/vehicles/{id}'
*/
update.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\VehicleController::update
* @see app/Http/Controllers/Operator/VehicleController.php:96
* @route '/api/operator/vehicles/{id}'
*/
update.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::update
* @see app/Http/Controllers/Operator/VehicleController.php:96
* @route '/api/operator/vehicles/{id}'
*/
const updateForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\VehicleController::update
* @see app/Http/Controllers/Operator/VehicleController.php:96
* @route '/api/operator/vehicles/{id}'
*/
updateForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const VehicleController = { index, store, show, update }

export default VehicleController