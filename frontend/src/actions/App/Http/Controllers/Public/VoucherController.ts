import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/public/vouchers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Public\VoucherController::index
 * @see app/Http/Controllers/Public/VoucherController.php:12
 * @route '/api/public/vouchers'
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
const VoucherController = { index }

export default VoucherController