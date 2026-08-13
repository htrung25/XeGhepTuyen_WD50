import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/public/provinces',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Public\ProvinceController::index
* @see app/Http/Controllers/Public/ProvinceController.php:12
* @route '/api/public/provinces'
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

const ProvinceController = { index }

export default ProvinceController