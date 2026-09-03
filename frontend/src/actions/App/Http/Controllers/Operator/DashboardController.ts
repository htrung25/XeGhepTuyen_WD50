import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
export const map = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: map.url(options),
    method: 'get',
})

map.definition = {
    methods: ["get","head"],
    url: '/api/operator/dashboard/map',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
map.url = (options?: RouteQueryOptions) => {
    return map.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
map.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: map.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
map.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: map.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
    const mapForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: map.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
        mapForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: map.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Operator\DashboardController::map
 * @see app/Http/Controllers/Operator/DashboardController.php:12
 * @route '/api/operator/dashboard/map'
 */
        mapForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: map.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    map.form = mapForm
const DashboardController = { map }

export default DashboardController