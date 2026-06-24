import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Driver\CheckinController::checkin
* @see app/Http/Controllers/Driver/CheckinController.php:19
* @route '/api/driver/checkin'
*/
export const checkin = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkin.url(options),
    method: 'post',
})

checkin.definition = {
    methods: ["post"],
    url: '/api/driver/checkin',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\CheckinController::checkin
* @see app/Http/Controllers/Driver/CheckinController.php:19
* @route '/api/driver/checkin'
*/
checkin.url = (options?: RouteQueryOptions) => {
    return checkin.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\CheckinController::checkin
* @see app/Http/Controllers/Driver/CheckinController.php:19
* @route '/api/driver/checkin'
*/
checkin.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: checkin.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\CheckinController::checkin
* @see app/Http/Controllers/Driver/CheckinController.php:19
* @route '/api/driver/checkin'
*/
const checkinForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkin.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\CheckinController::checkin
* @see app/Http/Controllers/Driver/CheckinController.php:19
* @route '/api/driver/checkin'
*/
checkinForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: checkin.url(options),
    method: 'post',
})

checkin.form = checkinForm

const CheckinController = { checkin }

export default CheckinController