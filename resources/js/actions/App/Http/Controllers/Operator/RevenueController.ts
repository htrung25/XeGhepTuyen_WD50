import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
export const summary = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/api/operator/revenue/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
const summaryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
summaryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::summary
* @see app/Http/Controllers/Operator/RevenueController.php:60
* @route '/api/operator/revenue/summary'
*/
summaryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

summary.form = summaryForm

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
export const daily = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: daily.url(options),
    method: 'get',
})

daily.definition = {
    methods: ["get","head"],
    url: '/api/operator/revenue/daily',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
daily.url = (options?: RouteQueryOptions) => {
    return daily.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
daily.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: daily.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
daily.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: daily.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
const dailyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: daily.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
dailyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: daily.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::daily
* @see app/Http/Controllers/Operator/RevenueController.php:101
* @route '/api/operator/revenue/daily'
*/
dailyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: daily.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

daily.form = dailyForm

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
export const byRoute = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: byRoute.url(options),
    method: 'get',
})

byRoute.definition = {
    methods: ["get","head"],
    url: '/api/operator/revenue/by-route',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
byRoute.url = (options?: RouteQueryOptions) => {
    return byRoute.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
byRoute.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: byRoute.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
byRoute.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: byRoute.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
const byRouteForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: byRoute.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
byRouteForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: byRoute.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byRoute
* @see app/Http/Controllers/Operator/RevenueController.php:123
* @route '/api/operator/revenue/by-route'
*/
byRouteForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: byRoute.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

byRoute.form = byRouteForm

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
export const byDriver = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: byDriver.url(options),
    method: 'get',
})

byDriver.definition = {
    methods: ["get","head"],
    url: '/api/operator/revenue/by-driver',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
byDriver.url = (options?: RouteQueryOptions) => {
    return byDriver.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
byDriver.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: byDriver.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
byDriver.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: byDriver.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
const byDriverForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: byDriver.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
byDriverForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: byDriver.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::byDriver
* @see app/Http/Controllers/Operator/RevenueController.php:146
* @route '/api/operator/revenue/by-driver'
*/
byDriverForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: byDriver.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

byDriver.form = byDriverForm

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
export const payouts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payouts.url(options),
    method: 'get',
})

payouts.definition = {
    methods: ["get","head"],
    url: '/api/operator/revenue/payouts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
payouts.url = (options?: RouteQueryOptions) => {
    return payouts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
payouts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payouts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
payouts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payouts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
const payoutsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payouts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
payoutsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payouts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::payouts
* @see app/Http/Controllers/Operator/RevenueController.php:188
* @route '/api/operator/revenue/payouts'
*/
payoutsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payouts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

payouts.form = payoutsForm

/**
* @see \App\Http\Controllers\Operator\RevenueController::requestPayout
* @see app/Http/Controllers/Operator/RevenueController.php:214
* @route '/api/operator/revenue/payout-request'
*/
export const requestPayout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestPayout.url(options),
    method: 'post',
})

requestPayout.definition = {
    methods: ["post"],
    url: '/api/operator/revenue/payout-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\RevenueController::requestPayout
* @see app/Http/Controllers/Operator/RevenueController.php:214
* @route '/api/operator/revenue/payout-request'
*/
requestPayout.url = (options?: RouteQueryOptions) => {
    return requestPayout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\RevenueController::requestPayout
* @see app/Http/Controllers/Operator/RevenueController.php:214
* @route '/api/operator/revenue/payout-request'
*/
requestPayout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestPayout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::requestPayout
* @see app/Http/Controllers/Operator/RevenueController.php:214
* @route '/api/operator/revenue/payout-request'
*/
const requestPayoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestPayout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\RevenueController::requestPayout
* @see app/Http/Controllers/Operator/RevenueController.php:214
* @route '/api/operator/revenue/payout-request'
*/
requestPayoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestPayout.url(options),
    method: 'post',
})

requestPayout.form = requestPayoutForm

const RevenueController = { summary, daily, byRoute, byDriver, payouts, requestPayout }

export default RevenueController