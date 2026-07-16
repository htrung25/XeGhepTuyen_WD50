import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
export const fleet = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: fleet.url(options),
    method: 'get',
})

fleet.definition = {
    methods: ["get","head"],
    url: '/api/operator/onboarding/fleet',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
fleet.url = (options?: RouteQueryOptions) => {
    return fleet.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
fleet.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: fleet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
fleet.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: fleet.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
const fleetForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: fleet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
fleetForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: fleet.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\OnboardingController::fleet
* @see app/Http/Controllers/Operator/OnboardingController.php:15
* @route '/api/operator/onboarding/fleet'
*/
fleetForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: fleet.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

fleet.form = fleetForm

const OnboardingController = { fleet }

export default OnboardingController