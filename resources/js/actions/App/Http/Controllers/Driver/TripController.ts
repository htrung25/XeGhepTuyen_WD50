import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/driver/trips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::index
* @see app/Http/Controllers/Driver/TripController.php:21
* @route '/api/driver/trips'
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
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
export const history = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/api/driver/trips/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
history.url = (options?: RouteQueryOptions) => {
    return history.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
history.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
history.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
const historyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
historyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::history
* @see app/Http/Controllers/Driver/TripController.php:145
* @route '/api/driver/trips/history'
*/
historyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: history.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

history.form = historyForm

/**
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/driver/trips/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
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
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::show
* @see app/Http/Controllers/Driver/TripController.php:44
* @route '/api/driver/trips/{id}'
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
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
export const passengers = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: passengers.url(args, options),
    method: 'get',
})

passengers.definition = {
    methods: ["get","head"],
    url: '/api/driver/trips/{id}/passengers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
passengers.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return passengers.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
passengers.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: passengers.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
passengers.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: passengers.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
const passengersForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: passengers.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
passengersForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: passengers.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\TripController::passengers
* @see app/Http/Controllers/Driver/TripController.php:56
* @route '/api/driver/trips/{id}/passengers'
*/
passengersForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: passengers.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

passengers.form = passengersForm

/**
* @see \App\Http\Controllers\Driver\TripController::start
* @see app/Http/Controllers/Driver/TripController.php:101
* @route '/api/driver/trips/{id}/start'
*/
export const start = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/api/driver/trips/{id}/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\TripController::start
* @see app/Http/Controllers/Driver/TripController.php:101
* @route '/api/driver/trips/{id}/start'
*/
start.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return start.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\TripController::start
* @see app/Http/Controllers/Driver/TripController.php:101
* @route '/api/driver/trips/{id}/start'
*/
start.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\TripController::start
* @see app/Http/Controllers/Driver/TripController.php:101
* @route '/api/driver/trips/{id}/start'
*/
const startForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\TripController::start
* @see app/Http/Controllers/Driver/TripController.php:101
* @route '/api/driver/trips/{id}/start'
*/
startForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(args, options),
    method: 'post',
})

start.form = startForm

/**
* @see \App\Http\Controllers\Driver\TripController::complete
* @see app/Http/Controllers/Driver/TripController.php:123
* @route '/api/driver/trips/{id}/complete'
*/
export const complete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

complete.definition = {
    methods: ["post"],
    url: '/api/driver/trips/{id}/complete',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\TripController::complete
* @see app/Http/Controllers/Driver/TripController.php:123
* @route '/api/driver/trips/{id}/complete'
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
* @see \App\Http\Controllers\Driver\TripController::complete
* @see app/Http/Controllers/Driver/TripController.php:123
* @route '/api/driver/trips/{id}/complete'
*/
complete.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\TripController::complete
* @see app/Http/Controllers/Driver/TripController.php:123
* @route '/api/driver/trips/{id}/complete'
*/
const completeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: complete.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\TripController::complete
* @see app/Http/Controllers/Driver/TripController.php:123
* @route '/api/driver/trips/{id}/complete'
*/
completeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: complete.url(args, options),
    method: 'post',
})

complete.form = completeForm

const TripController = { index, history, show, passengers, start, complete }

export default TripController