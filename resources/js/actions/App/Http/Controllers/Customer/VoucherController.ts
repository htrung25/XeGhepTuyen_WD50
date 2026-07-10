import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\VoucherController::apply
* @see app/Http/Controllers/Customer/VoucherController.php:19
* @route '/api/customer/vouchers/apply'
*/
export const apply = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apply.url(options),
    method: 'post',
})

apply.definition = {
    methods: ["post"],
    url: '/api/customer/vouchers/apply',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\VoucherController::apply
* @see app/Http/Controllers/Customer/VoucherController.php:19
* @route '/api/customer/vouchers/apply'
*/
apply.url = (options?: RouteQueryOptions) => {
    return apply.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\VoucherController::apply
* @see app/Http/Controllers/Customer/VoucherController.php:19
* @route '/api/customer/vouchers/apply'
*/
apply.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apply.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\VoucherController::apply
* @see app/Http/Controllers/Customer/VoucherController.php:19
* @route '/api/customer/vouchers/apply'
*/
const applyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: apply.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\VoucherController::apply
* @see app/Http/Controllers/Customer/VoucherController.php:19
* @route '/api/customer/vouchers/apply'
*/
applyForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: apply.url(options),
    method: 'post',
})

apply.form = applyForm

const VoucherController = { apply }

export default VoucherController