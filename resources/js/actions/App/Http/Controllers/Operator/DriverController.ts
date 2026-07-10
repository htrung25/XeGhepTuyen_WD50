import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/operator/drivers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::index
* @see app/Http/Controllers/Operator/DriverController.php:18
* @route '/api/operator/drivers'
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
* @see \App\Http\Controllers\Operator\DriverController::store
* @see app/Http/Controllers/Operator/DriverController.php:34
* @route '/api/operator/drivers'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/operator/drivers',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\DriverController::store
* @see app/Http/Controllers/Operator/DriverController.php:34
* @route '/api/operator/drivers'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\DriverController::store
* @see app/Http/Controllers/Operator/DriverController.php:34
* @route '/api/operator/drivers'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::store
* @see app/Http/Controllers/Operator/DriverController.php:34
* @route '/api/operator/drivers'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::store
* @see app/Http/Controllers/Operator/DriverController.php:34
* @route '/api/operator/drivers'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/operator/drivers/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
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
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::show
* @see app/Http/Controllers/Operator/DriverController.php:87
* @route '/api/operator/drivers/{id}'
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
* @see \App\Http\Controllers\Operator\DriverController::assignVehicle
* @see app/Http/Controllers/Operator/DriverController.php:99
* @route '/api/operator/drivers/{id}/vehicle'
*/
export const assignVehicle = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: assignVehicle.url(args, options),
    method: 'put',
})

assignVehicle.definition = {
    methods: ["put"],
    url: '/api/operator/drivers/{id}/vehicle',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\DriverController::assignVehicle
* @see app/Http/Controllers/Operator/DriverController.php:99
* @route '/api/operator/drivers/{id}/vehicle'
*/
assignVehicle.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return assignVehicle.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\DriverController::assignVehicle
* @see app/Http/Controllers/Operator/DriverController.php:99
* @route '/api/operator/drivers/{id}/vehicle'
*/
assignVehicle.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: assignVehicle.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::assignVehicle
* @see app/Http/Controllers/Operator/DriverController.php:99
* @route '/api/operator/drivers/{id}/vehicle'
*/
const assignVehicleForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assignVehicle.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::assignVehicle
* @see app/Http/Controllers/Operator/DriverController.php:99
* @route '/api/operator/drivers/{id}/vehicle'
*/
assignVehicleForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assignVehicle.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

assignVehicle.form = assignVehicleForm

/**
* @see \App\Http\Controllers\Operator\DriverController::updateStatus
* @see app/Http/Controllers/Operator/DriverController.php:136
* @route '/api/operator/drivers/{id}/status'
*/
export const updateStatus = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

updateStatus.definition = {
    methods: ["put"],
    url: '/api/operator/drivers/{id}/status',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\DriverController::updateStatus
* @see app/Http/Controllers/Operator/DriverController.php:136
* @route '/api/operator/drivers/{id}/status'
*/
updateStatus.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return updateStatus.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\DriverController::updateStatus
* @see app/Http/Controllers/Operator/DriverController.php:136
* @route '/api/operator/drivers/{id}/status'
*/
updateStatus.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::updateStatus
* @see app/Http/Controllers/Operator/DriverController.php:136
* @route '/api/operator/drivers/{id}/status'
*/
const updateStatusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::updateStatus
* @see app/Http/Controllers/Operator/DriverController.php:136
* @route '/api/operator/drivers/{id}/status'
*/
updateStatusForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateStatus.form = updateStatusForm

/**
* @see \App\Http\Controllers\Operator\DriverController::resetPassword
* @see app/Http/Controllers/Operator/DriverController.php:118
* @route '/api/operator/drivers/{id}/reset-password'
*/
export const resetPassword = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/api/operator/drivers/{id}/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\DriverController::resetPassword
* @see app/Http/Controllers/Operator/DriverController.php:118
* @route '/api/operator/drivers/{id}/reset-password'
*/
resetPassword.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resetPassword.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\DriverController::resetPassword
* @see app/Http/Controllers/Operator/DriverController.php:118
* @route '/api/operator/drivers/{id}/reset-password'
*/
resetPassword.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::resetPassword
* @see app/Http/Controllers/Operator/DriverController.php:118
* @route '/api/operator/drivers/{id}/reset-password'
*/
const resetPasswordForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetPassword.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\DriverController::resetPassword
* @see app/Http/Controllers/Operator/DriverController.php:118
* @route '/api/operator/drivers/{id}/reset-password'
*/
resetPasswordForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.form = resetPasswordForm

const DriverController = { index, store, show, assignVehicle, updateStatus, resetPassword }

export default DriverController