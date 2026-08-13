import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/operator/fare-rates',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\FareRateController::index
* @see app/Http/Controllers/Operator/FareRateController.php:15
* @route '/api/operator/fare-rates'
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
* @see \App\Http\Controllers\Operator\FareRateController::save
* @see app/Http/Controllers/Operator/FareRateController.php:33
* @route '/api/operator/fare-rates'
*/
export const save = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: save.url(options),
    method: 'put',
})

save.definition = {
    methods: ["put"],
    url: '/api/operator/fare-rates',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\FareRateController::save
* @see app/Http/Controllers/Operator/FareRateController.php:33
* @route '/api/operator/fare-rates'
*/
save.url = (options?: RouteQueryOptions) => {
    return save.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\FareRateController::save
* @see app/Http/Controllers/Operator/FareRateController.php:33
* @route '/api/operator/fare-rates'
*/
save.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: save.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\FareRateController::save
* @see app/Http/Controllers/Operator/FareRateController.php:33
* @route '/api/operator/fare-rates'
*/
const saveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\FareRateController::save
* @see app/Http/Controllers/Operator/FareRateController.php:33
* @route '/api/operator/fare-rates'
*/
saveForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: save.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

save.form = saveForm

const FareRateController = { index, save }

export default FareRateController