import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/admin/support/tickets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::index
* @see app/Http/Controllers/Admin/SupportController.php:22
* @route '/api/admin/support/tickets'
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
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/admin/support/tickets/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
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
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::show
* @see app/Http/Controllers/Admin/SupportController.php:81
* @route '/api/admin/support/tickets/{id}'
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
* @see \App\Http\Controllers\Admin\SupportController::reply
* @see app/Http/Controllers/Admin/SupportController.php:99
* @route '/api/admin/support/tickets/{id}/reply'
*/
export const reply = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

reply.definition = {
    methods: ["post"],
    url: '/api/admin/support/tickets/{id}/reply',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\SupportController::reply
* @see app/Http/Controllers/Admin/SupportController.php:99
* @route '/api/admin/support/tickets/{id}/reply'
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
* @see \App\Http\Controllers\Admin\SupportController::reply
* @see app/Http/Controllers/Admin/SupportController.php:99
* @route '/api/admin/support/tickets/{id}/reply'
*/
reply.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reply.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::reply
* @see app/Http/Controllers/Admin/SupportController.php:99
* @route '/api/admin/support/tickets/{id}/reply'
*/
const replyForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reply.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::reply
* @see app/Http/Controllers/Admin/SupportController.php:99
* @route '/api/admin/support/tickets/{id}/reply'
*/
replyForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reply.url(args, options),
    method: 'post',
})

reply.form = replyForm

/**
* @see \App\Http\Controllers\Admin\SupportController::assign
* @see app/Http/Controllers/Admin/SupportController.php:122
* @route '/api/admin/support/tickets/{id}/assign'
*/
export const assign = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assign.url(args, options),
    method: 'post',
})

assign.definition = {
    methods: ["post"],
    url: '/api/admin/support/tickets/{id}/assign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\SupportController::assign
* @see app/Http/Controllers/Admin/SupportController.php:122
* @route '/api/admin/support/tickets/{id}/assign'
*/
assign.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return assign.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\SupportController::assign
* @see app/Http/Controllers/Admin/SupportController.php:122
* @route '/api/admin/support/tickets/{id}/assign'
*/
assign.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::assign
* @see app/Http/Controllers/Admin/SupportController.php:122
* @route '/api/admin/support/tickets/{id}/assign'
*/
const assignForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::assign
* @see app/Http/Controllers/Admin/SupportController.php:122
* @route '/api/admin/support/tickets/{id}/assign'
*/
assignForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assign.url(args, options),
    method: 'post',
})

assign.form = assignForm

/**
* @see \App\Http\Controllers\Admin\SupportController::resolve
* @see app/Http/Controllers/Admin/SupportController.php:135
* @route '/api/admin/support/tickets/{id}/resolve'
*/
export const resolve = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(args, options),
    method: 'post',
})

resolve.definition = {
    methods: ["post"],
    url: '/api/admin/support/tickets/{id}/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\SupportController::resolve
* @see app/Http/Controllers/Admin/SupportController.php:135
* @route '/api/admin/support/tickets/{id}/resolve'
*/
resolve.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return resolve.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\SupportController::resolve
* @see app/Http/Controllers/Admin/SupportController.php:135
* @route '/api/admin/support/tickets/{id}/resolve'
*/
resolve.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::resolve
* @see app/Http/Controllers/Admin/SupportController.php:135
* @route '/api/admin/support/tickets/{id}/resolve'
*/
const resolveForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::resolve
* @see app/Http/Controllers/Admin/SupportController.php:135
* @route '/api/admin/support/tickets/{id}/resolve'
*/
resolveForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolve.url(args, options),
    method: 'post',
})

resolve.form = resolveForm

/**
* @see \App\Http\Controllers\Admin\SupportController::close
* @see app/Http/Controllers/Admin/SupportController.php:148
* @route '/api/admin/support/tickets/{id}/close'
*/
export const close = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/api/admin/support/tickets/{id}/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\SupportController::close
* @see app/Http/Controllers/Admin/SupportController.php:148
* @route '/api/admin/support/tickets/{id}/close'
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
* @see \App\Http\Controllers\Admin\SupportController::close
* @see app/Http/Controllers/Admin/SupportController.php:148
* @route '/api/admin/support/tickets/{id}/close'
*/
close.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::close
* @see app/Http/Controllers/Admin/SupportController.php:148
* @route '/api/admin/support/tickets/{id}/close'
*/
const closeForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\SupportController::close
* @see app/Http/Controllers/Admin/SupportController.php:148
* @route '/api/admin/support/tickets/{id}/close'
*/
closeForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(args, options),
    method: 'post',
})

close.form = closeForm

const SupportController = { index, show, reply, assign, resolve, close }

export default SupportController