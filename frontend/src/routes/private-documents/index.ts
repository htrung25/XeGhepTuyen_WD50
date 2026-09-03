import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/public/private-documents',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
    const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
        showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Public\PrivateDocumentController::show
 * @see app/Http/Controllers/Public/PrivateDocumentController.php:17
 * @route '/api/public/private-documents'
 */
        showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
const privateDocuments = {
    show: Object.assign(show, show),
}

export default privateDocuments