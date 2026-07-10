import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults, validateParameters } from './../../wayfinder'
/**
* @see routes/web.php:31
* @route '/{any?}'
*/
export const spa = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: spa.url(args, options),
    method: 'get',
})

spa.definition = {
    methods: ["get","head"],
    url: '/{any?}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:31
* @route '/{any?}'
*/
spa.url = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { any: args }
    }

    if (Array.isArray(args)) {
        args = {
            any: args[0],
        }
    }

    args = applyUrlDefaults(args)

    validateParameters(args, [
        "any",
    ])

    const parsedArgs = {
        any: args?.any,
    }

    return spa.definition.url
            .replace('{any?}', parsedArgs.any?.toString() ?? '')
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see routes/web.php:31
* @route '/{any?}'
*/
spa.get = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: spa.url(args, options),
    method: 'get',
})

/**
* @see routes/web.php:31
* @route '/{any?}'
*/
spa.head = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: spa.url(args, options),
    method: 'head',
})

/**
* @see routes/web.php:31
* @route '/{any?}'
*/
const spaForm = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: spa.url(args, options),
    method: 'get',
})

/**
* @see routes/web.php:31
* @route '/{any?}'
*/
spaForm.get = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: spa.url(args, options),
    method: 'get',
})

/**
* @see routes/web.php:31
* @route '/{any?}'
*/
spaForm.head = (args?: { any?: string | number } | [any: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: spa.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

spa.form = spaForm

const customer = {
    spa: Object.assign(spa, spa),
}

export default customer