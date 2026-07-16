import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Public\PartnerApplicationController::store
* @see app/Http/Controllers/Public/PartnerApplicationController.php:17
* @route '/api/public/partner-applications'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/public/partner-applications',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Public\PartnerApplicationController::store
* @see app/Http/Controllers/Public/PartnerApplicationController.php:17
* @route '/api/public/partner-applications'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\PartnerApplicationController::store
* @see app/Http/Controllers/Public/PartnerApplicationController.php:17
* @route '/api/public/partner-applications'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Public\PartnerApplicationController::store
* @see app/Http/Controllers/Public/PartnerApplicationController.php:17
* @route '/api/public/partner-applications'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Public\PartnerApplicationController::store
* @see app/Http/Controllers/Public/PartnerApplicationController.php:17
* @route '/api/public/partner-applications'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const PartnerApplicationController = { store }

export default PartnerApplicationController