import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\BookingController::lockSeats
* @see app/Http/Controllers/Customer/BookingController.php:54
* @route '/api/customer/bookings/lock-seats'
*/
export const lockSeats = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lockSeats.url(options),
    method: 'post',
})

lockSeats.definition = {
    methods: ["post"],
    url: '/api/customer/bookings/lock-seats',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\BookingController::lockSeats
* @see app/Http/Controllers/Customer/BookingController.php:54
* @route '/api/customer/bookings/lock-seats'
*/
lockSeats.url = (options?: RouteQueryOptions) => {
    return lockSeats.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\BookingController::lockSeats
* @see app/Http/Controllers/Customer/BookingController.php:54
* @route '/api/customer/bookings/lock-seats'
*/
lockSeats.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lockSeats.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::lockSeats
* @see app/Http/Controllers/Customer/BookingController.php:54
* @route '/api/customer/bookings/lock-seats'
*/
const lockSeatsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lockSeats.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::lockSeats
* @see app/Http/Controllers/Customer/BookingController.php:54
* @route '/api/customer/bookings/lock-seats'
*/
lockSeatsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lockSeats.url(options),
    method: 'post',
})

lockSeats.form = lockSeatsForm

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/customer/bookings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::index
* @see app/Http/Controllers/Customer/BookingController.php:24
* @route '/api/customer/bookings'
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
* @see \App\Http\Controllers\Customer\BookingController::store
* @see app/Http/Controllers/Customer/BookingController.php:75
* @route '/api/customer/bookings'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/customer/bookings',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\BookingController::store
* @see app/Http/Controllers/Customer/BookingController.php:75
* @route '/api/customer/bookings'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\BookingController::store
* @see app/Http/Controllers/Customer/BookingController.php:75
* @route '/api/customer/bookings'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::store
* @see app/Http/Controllers/Customer/BookingController.php:75
* @route '/api/customer/bookings'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::store
* @see app/Http/Controllers/Customer/BookingController.php:75
* @route '/api/customer/bookings'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/customer/bookings/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::show
* @see app/Http/Controllers/Customer/BookingController.php:43
* @route '/api/customer/bookings/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Customer\BookingController::cancel
* @see app/Http/Controllers/Customer/BookingController.php:100
* @route '/api/customer/bookings/{id}/cancel'
*/
export const cancel = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

cancel.definition = {
    methods: ["post"],
    url: '/api/customer/bookings/{id}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\BookingController::cancel
* @see app/Http/Controllers/Customer/BookingController.php:100
* @route '/api/customer/bookings/{id}/cancel'
*/
cancel.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return cancel.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\BookingController::cancel
* @see app/Http/Controllers/Customer/BookingController.php:100
* @route '/api/customer/bookings/{id}/cancel'
*/
cancel.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::cancel
* @see app/Http/Controllers/Customer/BookingController.php:100
* @route '/api/customer/bookings/{id}/cancel'
*/
const cancelForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::cancel
* @see app/Http/Controllers/Customer/BookingController.php:100
* @route '/api/customer/bookings/{id}/cancel'
*/
cancelForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel.url(args, options),
    method: 'post',
})

cancel.form = cancelForm

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
export const qr = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qr.url(args, options),
    method: 'get',
})

qr.definition = {
    methods: ["get","head"],
    url: '/api/customer/bookings/{id}/qr',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
qr.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return qr.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
qr.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qr.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
qr.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: qr.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
const qrForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: qr.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
qrForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: qr.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\BookingController::qr
* @see app/Http/Controllers/Customer/BookingController.php:130
* @route '/api/customer/bookings/{id}/qr'
*/
qrForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: qr.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

qr.form = qrForm

const BookingController = { lockSeats, index, store, show, cancel, qr }

export default BookingController