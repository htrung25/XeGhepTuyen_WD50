import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\ReviewController::store
* @see app/Http/Controllers/Customer/ReviewController.php:16
* @route '/api/customer/reviews'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/customer/reviews',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\ReviewController::store
* @see app/Http/Controllers/Customer/ReviewController.php:16
* @route '/api/customer/reviews'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\ReviewController::store
* @see app/Http/Controllers/Customer/ReviewController.php:16
* @route '/api/customer/reviews'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\ReviewController::store
* @see app/Http/Controllers/Customer/ReviewController.php:16
* @route '/api/customer/reviews'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\ReviewController::store
* @see app/Http/Controllers/Customer/ReviewController.php:16
* @route '/api/customer/reviews'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const ReviewController = { store }

export default ReviewController