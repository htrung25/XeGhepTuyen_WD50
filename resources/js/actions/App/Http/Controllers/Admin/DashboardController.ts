import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
export const pendingCounts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingCounts.url(options),
    method: 'get',
})

pendingCounts.definition = {
    methods: ["get","head"],
    url: '/api/admin/pending-counts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
pendingCounts.url = (options?: RouteQueryOptions) => {
    return pendingCounts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
pendingCounts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pendingCounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
pendingCounts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pendingCounts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
const pendingCountsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pendingCounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
pendingCountsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pendingCounts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::pendingCounts
* @see app/Http/Controllers/Admin/DashboardController.php:79
* @route '/api/admin/pending-counts'
*/
pendingCountsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pendingCounts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

pendingCounts.form = pendingCountsForm

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/admin/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::index
* @see app/Http/Controllers/Admin/DashboardController.php:25
* @route '/api/admin/dashboard'
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
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
*/
export const map = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: map.url(options),
    method: 'get',
})

map.definition = {
    methods: ["get","head"],
    url: '/api/admin/dashboard/map',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
*/
map.url = (options?: RouteQueryOptions) => {
    return map.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
*/
map.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: map.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
*/
map.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: map.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
*/
const mapForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: map.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
*/
mapForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: map.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\DashboardController::map
* @see app/Http/Controllers/Admin/DashboardController.php:67
* @route '/api/admin/dashboard/map'
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

const DashboardController = { pendingCounts, index, map }

export default DashboardController