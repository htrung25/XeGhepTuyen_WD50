import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Driver\LocationController::update
* @see app/Http/Controllers/Driver/LocationController.php:15
* @route '/api/driver/location'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/api/driver/location',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\LocationController::update
* @see app/Http/Controllers/Driver/LocationController.php:15
* @route '/api/driver/location'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\LocationController::update
* @see app/Http/Controllers/Driver/LocationController.php:15
* @route '/api/driver/location'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\LocationController::update
* @see app/Http/Controllers/Driver/LocationController.php:15
* @route '/api/driver/location'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\LocationController::update
* @see app/Http/Controllers/Driver/LocationController.php:15
* @route '/api/driver/location'
*/
updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

update.form = updateForm

const LocationController = { update }

export default LocationController