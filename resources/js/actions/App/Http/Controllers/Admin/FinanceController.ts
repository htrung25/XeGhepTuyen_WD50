import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
*/
export const summary = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

summary.definition = {
    methods: ["get","head"],
    url: '/api/admin/finance/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
*/
summary.url = (options?: RouteQueryOptions) => {
    return summary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
*/
summary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
*/
summary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: summary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
*/
const summaryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
*/
summaryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: summary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::summary
* @see app/Http/Controllers/Admin/FinanceController.php:29
* @route '/api/admin/finance/summary'
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
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/api/admin/finance/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
*/
const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
*/
transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::transactions
* @see app/Http/Controllers/Admin/FinanceController.php:192
* @route '/api/admin/finance/transactions'
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
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
export const refunds = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: refunds.url(options),
    method: 'get',
})

refunds.definition = {
    methods: ["get","head"],
    url: '/api/admin/finance/refunds',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
refunds.url = (options?: RouteQueryOptions) => {
    return refunds.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
refunds.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: refunds.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
refunds.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: refunds.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
const refundsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: refunds.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
refundsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: refunds.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::refunds
* @see app/Http/Controllers/Admin/FinanceController.php:245
* @route '/api/admin/finance/refunds'
*/
refundsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: refunds.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

refunds.form = refundsForm

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
export const commissions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commissions.url(options),
    method: 'get',
})

commissions.definition = {
    methods: ["get","head"],
    url: '/api/admin/finance/commissions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
commissions.url = (options?: RouteQueryOptions) => {
    return commissions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
commissions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commissions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
commissions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: commissions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
const commissionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commissions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
commissionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commissions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::commissions
* @see app/Http/Controllers/Admin/FinanceController.php:61
* @route '/api/admin/finance/commissions'
*/
commissionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commissions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

commissions.form = commissionsForm

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
*/
export const payouts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payouts.url(options),
    method: 'get',
})

payouts.definition = {
    methods: ["get","head"],
    url: '/api/admin/finance/payouts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
*/
payouts.url = (options?: RouteQueryOptions) => {
    return payouts.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
*/
payouts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payouts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
*/
payouts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payouts.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
*/
const payoutsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payouts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
*/
payoutsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payouts.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::payouts
* @see app/Http/Controllers/Admin/FinanceController.php:312
* @route '/api/admin/finance/payouts'
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
* @see \App\Http\Controllers\Admin\FinanceController::payout
* @see app/Http/Controllers/Admin/FinanceController.php:70
* @route '/api/admin/finance/payouts'
*/
export const payout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: payout.url(options),
    method: 'post',
})

payout.definition = {
    methods: ["post"],
    url: '/api/admin/finance/payouts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::payout
* @see app/Http/Controllers/Admin/FinanceController.php:70
* @route '/api/admin/finance/payouts'
*/
payout.url = (options?: RouteQueryOptions) => {
    return payout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::payout
* @see app/Http/Controllers/Admin/FinanceController.php:70
* @route '/api/admin/finance/payouts'
*/
payout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: payout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::payout
* @see app/Http/Controllers/Admin/FinanceController.php:70
* @route '/api/admin/finance/payouts'
*/
const payoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: payout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::payout
* @see app/Http/Controllers/Admin/FinanceController.php:70
* @route '/api/admin/finance/payouts'
*/
payoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: payout.url(options),
    method: 'post',
})

payout.form = payoutForm

/**
* @see \App\Http\Controllers\Admin\FinanceController::refund
* @see app/Http/Controllers/Admin/FinanceController.php:263
* @route '/api/admin/finance/refund/{booking}'
*/
export const refund = (args: { booking: string | number } | [booking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refund.url(args, options),
    method: 'post',
})

refund.definition = {
    methods: ["post"],
    url: '/api/admin/finance/refund/{booking}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\FinanceController::refund
* @see app/Http/Controllers/Admin/FinanceController.php:263
* @route '/api/admin/finance/refund/{booking}'
*/
refund.url = (args: { booking: string | number } | [booking: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { booking: args }
    }

    if (Array.isArray(args)) {
        args = {
            booking: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        booking: args.booking,
    }

    return refund.definition.url
            .replace('{booking}', parsedArgs.booking.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\FinanceController::refund
* @see app/Http/Controllers/Admin/FinanceController.php:263
* @route '/api/admin/finance/refund/{booking}'
*/
refund.post = (args: { booking: string | number } | [booking: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: refund.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::refund
* @see app/Http/Controllers/Admin/FinanceController.php:263
* @route '/api/admin/finance/refund/{booking}'
*/
const refundForm = (args: { booking: string | number } | [booking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refund.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\FinanceController::refund
* @see app/Http/Controllers/Admin/FinanceController.php:263
* @route '/api/admin/finance/refund/{booking}'
*/
refundForm.post = (args: { booking: string | number } | [booking: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: refund.url(args, options),
    method: 'post',
})

refund.form = refundForm

const FinanceController = { summary, transactions, refunds, commissions, payouts, payout, refund }

export default FinanceController