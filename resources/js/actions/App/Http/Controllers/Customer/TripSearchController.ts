import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
const searchc2969e39b5e42cd657a66c4c0874b6ef = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: searchc2969e39b5e42cd657a66c4c0874b6ef.url(options),
    method: 'get',
})

searchc2969e39b5e42cd657a66c4c0874b6ef.definition = {
    methods: ["get","head"],
    url: '/api/public/trips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
searchc2969e39b5e42cd657a66c4c0874b6ef.url = (options?: RouteQueryOptions) => {
    return searchc2969e39b5e42cd657a66c4c0874b6ef.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
searchc2969e39b5e42cd657a66c4c0874b6ef.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: searchc2969e39b5e42cd657a66c4c0874b6ef.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
searchc2969e39b5e42cd657a66c4c0874b6ef.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: searchc2969e39b5e42cd657a66c4c0874b6ef.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
const searchc2969e39b5e42cd657a66c4c0874b6efForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: searchc2969e39b5e42cd657a66c4c0874b6ef.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
searchc2969e39b5e42cd657a66c4c0874b6efForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: searchc2969e39b5e42cd657a66c4c0874b6ef.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/public/trips'
*/
searchc2969e39b5e42cd657a66c4c0874b6efForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: searchc2969e39b5e42cd657a66c4c0874b6ef.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

searchc2969e39b5e42cd657a66c4c0874b6ef.form = searchc2969e39b5e42cd657a66c4c0874b6efForm
/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
const search638ec3cd4998cbb42f67e89f8349283b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search638ec3cd4998cbb42f67e89f8349283b.url(options),
    method: 'get',
})

search638ec3cd4998cbb42f67e89f8349283b.definition = {
    methods: ["get","head"],
    url: '/api/customer/trips',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
search638ec3cd4998cbb42f67e89f8349283b.url = (options?: RouteQueryOptions) => {
    return search638ec3cd4998cbb42f67e89f8349283b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
search638ec3cd4998cbb42f67e89f8349283b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search638ec3cd4998cbb42f67e89f8349283b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
search638ec3cd4998cbb42f67e89f8349283b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search638ec3cd4998cbb42f67e89f8349283b.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
const search638ec3cd4998cbb42f67e89f8349283bForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search638ec3cd4998cbb42f67e89f8349283b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
search638ec3cd4998cbb42f67e89f8349283bForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search638ec3cd4998cbb42f67e89f8349283b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::search
* @see app/Http/Controllers/Customer/TripSearchController.php:43
* @route '/api/customer/trips'
*/
search638ec3cd4998cbb42f67e89f8349283bForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search638ec3cd4998cbb42f67e89f8349283b.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

search638ec3cd4998cbb42f67e89f8349283b.form = search638ec3cd4998cbb42f67e89f8349283bForm

/**
* Multiple routes resolve to \App\Http\Controllers\Customer\TripSearchController::search, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `search['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const search = {
    '/api/public/trips': searchc2969e39b5e42cd657a66c4c0874b6ef,
    '/api/customer/trips': search638ec3cd4998cbb42f67e89f8349283b,
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
const show8490f1baff0bcab313f9b43779fe119b = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show8490f1baff0bcab313f9b43779fe119b.url(args, options),
    method: 'get',
})

show8490f1baff0bcab313f9b43779fe119b.definition = {
    methods: ["get","head"],
    url: '/api/public/trips/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
show8490f1baff0bcab313f9b43779fe119b.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show8490f1baff0bcab313f9b43779fe119b.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
show8490f1baff0bcab313f9b43779fe119b.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show8490f1baff0bcab313f9b43779fe119b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
show8490f1baff0bcab313f9b43779fe119b.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show8490f1baff0bcab313f9b43779fe119b.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
const show8490f1baff0bcab313f9b43779fe119bForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show8490f1baff0bcab313f9b43779fe119b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
show8490f1baff0bcab313f9b43779fe119bForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show8490f1baff0bcab313f9b43779fe119b.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/public/trips/{id}'
*/
show8490f1baff0bcab313f9b43779fe119bForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show8490f1baff0bcab313f9b43779fe119b.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show8490f1baff0bcab313f9b43779fe119b.form = show8490f1baff0bcab313f9b43779fe119bForm
/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
const show56e290f360a25f520e46f479bdc45c62 = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show56e290f360a25f520e46f479bdc45c62.url(args, options),
    method: 'get',
})

show56e290f360a25f520e46f479bdc45c62.definition = {
    methods: ["get","head"],
    url: '/api/customer/trips/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
show56e290f360a25f520e46f479bdc45c62.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show56e290f360a25f520e46f479bdc45c62.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
show56e290f360a25f520e46f479bdc45c62.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show56e290f360a25f520e46f479bdc45c62.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
show56e290f360a25f520e46f479bdc45c62.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show56e290f360a25f520e46f479bdc45c62.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
const show56e290f360a25f520e46f479bdc45c62Form = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show56e290f360a25f520e46f479bdc45c62.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
show56e290f360a25f520e46f479bdc45c62Form.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show56e290f360a25f520e46f479bdc45c62.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::show
* @see app/Http/Controllers/Customer/TripSearchController.php:67
* @route '/api/customer/trips/{id}'
*/
show56e290f360a25f520e46f479bdc45c62Form.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show56e290f360a25f520e46f479bdc45c62.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show56e290f360a25f520e46f479bdc45c62.form = show56e290f360a25f520e46f479bdc45c62Form

/**
* Multiple routes resolve to \App\Http\Controllers\Customer\TripSearchController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/public/trips/{id}': show8490f1baff0bcab313f9b43779fe119b,
    '/api/customer/trips/{id}': show56e290f360a25f520e46f479bdc45c62,
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
const seats2b16853009a97d9ec9ec7e7d28645329 = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seats2b16853009a97d9ec9ec7e7d28645329.url(args, options),
    method: 'get',
})

seats2b16853009a97d9ec9ec7e7d28645329.definition = {
    methods: ["get","head"],
    url: '/api/public/trips/{id}/seats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
seats2b16853009a97d9ec9ec7e7d28645329.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return seats2b16853009a97d9ec9ec7e7d28645329.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
seats2b16853009a97d9ec9ec7e7d28645329.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seats2b16853009a97d9ec9ec7e7d28645329.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
seats2b16853009a97d9ec9ec7e7d28645329.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: seats2b16853009a97d9ec9ec7e7d28645329.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
const seats2b16853009a97d9ec9ec7e7d28645329Form = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: seats2b16853009a97d9ec9ec7e7d28645329.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
seats2b16853009a97d9ec9ec7e7d28645329Form.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: seats2b16853009a97d9ec9ec7e7d28645329.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/public/trips/{id}/seats'
*/
seats2b16853009a97d9ec9ec7e7d28645329Form.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: seats2b16853009a97d9ec9ec7e7d28645329.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

seats2b16853009a97d9ec9ec7e7d28645329.form = seats2b16853009a97d9ec9ec7e7d28645329Form
/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
const seats6cbd161ff7def05793c54d4a3538cb5c = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seats6cbd161ff7def05793c54d4a3538cb5c.url(args, options),
    method: 'get',
})

seats6cbd161ff7def05793c54d4a3538cb5c.definition = {
    methods: ["get","head"],
    url: '/api/customer/trips/{id}/seats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
seats6cbd161ff7def05793c54d4a3538cb5c.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return seats6cbd161ff7def05793c54d4a3538cb5c.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
seats6cbd161ff7def05793c54d4a3538cb5c.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: seats6cbd161ff7def05793c54d4a3538cb5c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
seats6cbd161ff7def05793c54d4a3538cb5c.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: seats6cbd161ff7def05793c54d4a3538cb5c.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
const seats6cbd161ff7def05793c54d4a3538cb5cForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: seats6cbd161ff7def05793c54d4a3538cb5c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
seats6cbd161ff7def05793c54d4a3538cb5cForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: seats6cbd161ff7def05793c54d4a3538cb5c.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TripSearchController::seats
* @see app/Http/Controllers/Customer/TripSearchController.php:81
* @route '/api/customer/trips/{id}/seats'
*/
seats6cbd161ff7def05793c54d4a3538cb5cForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: seats6cbd161ff7def05793c54d4a3538cb5c.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

seats6cbd161ff7def05793c54d4a3538cb5c.form = seats6cbd161ff7def05793c54d4a3538cb5cForm

/**
* Multiple routes resolve to \App\Http\Controllers\Customer\TripSearchController::seats, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `seats['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const seats = {
    '/api/public/trips/{id}/seats': seats2b16853009a97d9ec9ec7e7d28645329,
    '/api/customer/trips/{id}/seats': seats6cbd161ff7def05793c54d4a3538cb5c,
}

const TripSearchController = { search, show, seats }

export default TripSearchController