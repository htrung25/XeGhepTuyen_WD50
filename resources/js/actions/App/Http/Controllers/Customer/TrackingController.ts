import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
export const trackByCode = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trackByCode.url(args, options),
    method: 'get',
})

trackByCode.definition = {
    methods: ["get","head"],
    url: '/api/customer/trips/{trackingCode}/track',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
trackByCode.url = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trackingCode: args }
    }

    if (Array.isArray(args)) {
        args = {
            trackingCode: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        trackingCode: args.trackingCode,
    }

    return trackByCode.definition.url
            .replace('{trackingCode}', parsedArgs.trackingCode.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
trackByCode.get = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trackByCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
trackByCode.head = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: trackByCode.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
const trackByCodeForm = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trackByCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
trackByCodeForm.get = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trackByCode.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByCode
* @see app/Http/Controllers/Customer/TrackingController.php:44
* @route '/api/customer/trips/{trackingCode}/track'
*/
trackByCodeForm.head = (args: { trackingCode: string | number } | [trackingCode: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trackByCode.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

trackByCode.form = trackByCodeForm

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
export const trackByBooking = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trackByBooking.url(args, options),
    method: 'get',
})

trackByBooking.definition = {
    methods: ["get","head"],
    url: '/api/customer/bookings/{id}/track',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
trackByBooking.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return trackByBooking.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
trackByBooking.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trackByBooking.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
trackByBooking.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: trackByBooking.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
const trackByBookingForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trackByBooking.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
trackByBookingForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trackByBooking.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\TrackingController::trackByBooking
* @see app/Http/Controllers/Customer/TrackingController.php:20
* @route '/api/customer/bookings/{id}/track'
*/
trackByBookingForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trackByBooking.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

trackByBooking.form = trackByBookingForm

const TrackingController = { trackByCode, trackByBooking }

export default TrackingController