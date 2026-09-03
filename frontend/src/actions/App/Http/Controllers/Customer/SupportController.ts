import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/customer/support/tickets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Customer\SupportController::index
 * @see app/Http/Controllers/Customer/SupportController.php:22
 * @route '/api/customer/support/tickets'
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
* @see \App\Http\Controllers\Customer\SupportController::store
 * @see app/Http/Controllers/Customer/SupportController.php:69
 * @route '/api/customer/support/tickets'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/customer/support/tickets',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\SupportController::store
 * @see app/Http/Controllers/Customer/SupportController.php:69
 * @route '/api/customer/support/tickets'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\SupportController::store
 * @see app/Http/Controllers/Customer/SupportController.php:69
 * @route '/api/customer/support/tickets'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Customer\SupportController::store
 * @see app/Http/Controllers/Customer/SupportController.php:69
 * @route '/api/customer/support/tickets'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Customer\SupportController::store
 * @see app/Http/Controllers/Customer/SupportController.php:69
 * @route '/api/customer/support/tickets'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
 */
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/customer/support/tickets/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
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
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
 */
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
 */
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
 */
    const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
 */
        showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Customer\SupportController::show
 * @see app/Http/Controllers/Customer/SupportController.php:84
 * @route '/api/customer/support/tickets/{id}'
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
* @see \App\Http\Controllers\Customer\SupportController::reply
 * @see app/Http/Controllers/Customer/SupportController.php:109
 * @route '/api/customer/support/tickets/{id}/reply'
 */
export const reply = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

reply.definition = {
    methods: ["post"],
    url: '/api/customer/support/tickets/{id}/reply',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\SupportController::reply
 * @see app/Http/Controllers/Customer/SupportController.php:109
 * @route '/api/customer/support/tickets/{id}/reply'
 */
reply.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reply.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\SupportController::reply
 * @see app/Http/Controllers/Customer/SupportController.php:109
 * @route '/api/customer/support/tickets/{id}/reply'
 */
reply.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Customer\SupportController::reply
 * @see app/Http/Controllers/Customer/SupportController.php:109
 * @route '/api/customer/support/tickets/{id}/reply'
 */
    const replyForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: reply.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Customer\SupportController::reply
 * @see app/Http/Controllers/Customer/SupportController.php:109
 * @route '/api/customer/support/tickets/{id}/reply'
 */
        replyForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: reply.url(args, options),
            method: 'post',
        })
    
    reply.form = replyForm
/**
* @see \App\Http\Controllers\Customer\SupportController::close
 * @see app/Http/Controllers/Customer/SupportController.php:136
 * @route '/api/customer/support/tickets/{id}/close'
 */
export const close = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/api/customer/support/tickets/{id}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\SupportController::close
 * @see app/Http/Controllers/Customer/SupportController.php:136
 * @route '/api/customer/support/tickets/{id}/close'
 */
close.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return close.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\SupportController::close
 * @see app/Http/Controllers/Customer/SupportController.php:136
 * @route '/api/customer/support/tickets/{id}/close'
 */
close.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Customer\SupportController::close
 * @see app/Http/Controllers/Customer/SupportController.php:136
 * @route '/api/customer/support/tickets/{id}/close'
 */
    const closeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: close.url(args, options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Customer\SupportController::close
 * @see app/Http/Controllers/Customer/SupportController.php:136
 * @route '/api/customer/support/tickets/{id}/close'
 */
        closeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: close.url(args, options),
            method: 'post',
        })
    
    close.form = closeForm
const SupportController = { index, store, show, reply, close }

export default SupportController