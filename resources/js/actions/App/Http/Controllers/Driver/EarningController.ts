import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/driver/earnings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::index
* @see app/Http/Controllers/Driver/EarningController.php:20
* @route '/api/driver/earnings'
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
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/api/driver/earnings/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\EarningController::transactions
* @see app/Http/Controllers/Driver/EarningController.php:64
* @route '/api/driver/earnings/transactions'
*/
transactionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

transactions.form = transactionsForm

const EarningController = { index, transactions }

export default EarningController