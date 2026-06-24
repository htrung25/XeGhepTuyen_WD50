import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\PaymentController::momoCallback
* @see app/Http/Controllers/Customer/PaymentController.php:56
* @route '/api/customer/payments/momo/callback'
*/
export const momoCallback = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: momoCallback.url(options),
    method: 'post',
})

momoCallback.definition = {
    methods: ["post"],
    url: '/api/customer/payments/momo/callback',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\PaymentController::momoCallback
* @see app/Http/Controllers/Customer/PaymentController.php:56
* @route '/api/customer/payments/momo/callback'
*/
momoCallback.url = (options?: RouteQueryOptions) => {
    return momoCallback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\PaymentController::momoCallback
* @see app/Http/Controllers/Customer/PaymentController.php:56
* @route '/api/customer/payments/momo/callback'
*/
momoCallback.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: momoCallback.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::momoCallback
* @see app/Http/Controllers/Customer/PaymentController.php:56
* @route '/api/customer/payments/momo/callback'
*/
const momoCallbackForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: momoCallback.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::momoCallback
* @see app/Http/Controllers/Customer/PaymentController.php:56
* @route '/api/customer/payments/momo/callback'
*/
momoCallbackForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: momoCallback.url(options),
    method: 'post',
})

momoCallback.form = momoCallbackForm

/**
* @see \App\Http\Controllers\Customer\PaymentController::vnpayCallback
* @see app/Http/Controllers/Customer/PaymentController.php:70
* @route '/api/customer/payments/vnpay/callback'
*/
export const vnpayCallback = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: vnpayCallback.url(options),
    method: 'post',
})

vnpayCallback.definition = {
    methods: ["post"],
    url: '/api/customer/payments/vnpay/callback',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\PaymentController::vnpayCallback
* @see app/Http/Controllers/Customer/PaymentController.php:70
* @route '/api/customer/payments/vnpay/callback'
*/
vnpayCallback.url = (options?: RouteQueryOptions) => {
    return vnpayCallback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\PaymentController::vnpayCallback
* @see app/Http/Controllers/Customer/PaymentController.php:70
* @route '/api/customer/payments/vnpay/callback'
*/
vnpayCallback.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: vnpayCallback.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::vnpayCallback
* @see app/Http/Controllers/Customer/PaymentController.php:70
* @route '/api/customer/payments/vnpay/callback'
*/
const vnpayCallbackForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: vnpayCallback.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::vnpayCallback
* @see app/Http/Controllers/Customer/PaymentController.php:70
* @route '/api/customer/payments/vnpay/callback'
*/
vnpayCallbackForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: vnpayCallback.url(options),
    method: 'post',
})

vnpayCallback.form = vnpayCallbackForm

/**
* @see \App\Http\Controllers\Customer\PaymentController::initiate
* @see app/Http/Controllers/Customer/PaymentController.php:25
* @route '/api/customer/payments/initiate'
*/
export const initiate = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initiate.url(options),
    method: 'post',
})

initiate.definition = {
    methods: ["post"],
    url: '/api/customer/payments/initiate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\PaymentController::initiate
* @see app/Http/Controllers/Customer/PaymentController.php:25
* @route '/api/customer/payments/initiate'
*/
initiate.url = (options?: RouteQueryOptions) => {
    return initiate.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\PaymentController::initiate
* @see app/Http/Controllers/Customer/PaymentController.php:25
* @route '/api/customer/payments/initiate'
*/
initiate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: initiate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::initiate
* @see app/Http/Controllers/Customer/PaymentController.php:25
* @route '/api/customer/payments/initiate'
*/
const initiateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: initiate.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::initiate
* @see app/Http/Controllers/Customer/PaymentController.php:25
* @route '/api/customer/payments/initiate'
*/
initiateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: initiate.url(options),
    method: 'post',
})

initiate.form = initiateForm

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
export const status = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

status.definition = {
    methods: ["get","head"],
    url: '/api/customer/payments/{bookingId}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
status.url = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bookingId: args }
    }

    if (Array.isArray(args)) {
        args = {
            bookingId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        bookingId: args.bookingId,
    }

    return status.definition.url
            .replace('{bookingId}', parsedArgs.bookingId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
status.get = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
status.head = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: status.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
const statusForm = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
statusForm.get = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::status
* @see app/Http/Controllers/Customer/PaymentController.php:84
* @route '/api/customer/payments/{bookingId}/status'
*/
statusForm.head = (args: { bookingId: string | number } | [bookingId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: status.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

status.form = statusForm

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
export const wallet = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

wallet.definition = {
    methods: ["get","head"],
    url: '/api/customer/wallet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
wallet.url = (options?: RouteQueryOptions) => {
    return wallet.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
wallet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
wallet.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: wallet.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
const walletForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
walletForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\PaymentController::wallet
* @see app/Http/Controllers/Customer/PaymentController.php:102
* @route '/api/customer/wallet'
*/
walletForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: wallet.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

wallet.form = walletForm

const PaymentController = { momoCallback, vnpayCallback, initiate, status, wallet }

export default PaymentController