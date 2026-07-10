import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
export const balance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: balance.url(options),
    method: 'get',
})

balance.definition = {
    methods: ["get","head"],
    url: '/api/customer/wallet/balance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
balance.url = (options?: RouteQueryOptions) => {
    return balance.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
balance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: balance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
balance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: balance.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
const balanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: balance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
balanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: balance.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::balance
* @see app/Http/Controllers/Customer/WalletController.php:14
* @route '/api/customer/wallet/balance'
*/
balanceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: balance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

balance.form = balanceForm

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/api/customer/wallet/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
*/
const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
*/
transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::transactions
* @see app/Http/Controllers/Customer/WalletController.php:25
* @route '/api/customer/wallet/transactions'
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

/**
* @see \App\Http\Controllers\Customer\WalletController::topup
* @see app/Http/Controllers/Customer/WalletController.php:37
* @route '/api/customer/wallet/topup'
*/
export const topup = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: topup.url(options),
    method: 'post',
})

topup.definition = {
    methods: ["post"],
    url: '/api/customer/wallet/topup',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\WalletController::topup
* @see app/Http/Controllers/Customer/WalletController.php:37
* @route '/api/customer/wallet/topup'
*/
topup.url = (options?: RouteQueryOptions) => {
    return topup.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\WalletController::topup
* @see app/Http/Controllers/Customer/WalletController.php:37
* @route '/api/customer/wallet/topup'
*/
topup.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: topup.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::topup
* @see app/Http/Controllers/Customer/WalletController.php:37
* @route '/api/customer/wallet/topup'
*/
const topupForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: topup.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\WalletController::topup
* @see app/Http/Controllers/Customer/WalletController.php:37
* @route '/api/customer/wallet/topup'
*/
topupForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: topup.url(options),
    method: 'post',
})

topup.form = topupForm

const WalletController = { balance, transactions, topup }

export default WalletController