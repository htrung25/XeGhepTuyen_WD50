import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Operator\AuthController::login
* @see app/Http/Controllers/Operator/AuthController.php:17
* @route '/api/operator/auth/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/api/operator/auth/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\AuthController::login
* @see app/Http/Controllers/Operator/AuthController.php:17
* @route '/api/operator/auth/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\AuthController::login
* @see app/Http/Controllers/Operator/AuthController.php:17
* @route '/api/operator/auth/login'
*/
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::login
* @see app/Http/Controllers/Operator/AuthController.php:17
* @route '/api/operator/auth/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::login
* @see app/Http/Controllers/Operator/AuthController.php:17
* @route '/api/operator/auth/login'
*/
loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

login.form = loginForm

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
export const me = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

me.definition = {
    methods: ["get","head"],
    url: '/api/operator/auth/me',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
me.url = (options?: RouteQueryOptions) => {
    return me.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
me.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
me.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: me.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
const meForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
meForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::me
* @see app/Http/Controllers/Operator/AuthController.php:53
* @route '/api/operator/auth/me'
*/
meForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

me.form = meForm

/**
* @see \App\Http\Controllers\Operator\AuthController::logout
* @see app/Http/Controllers/Operator/AuthController.php:46
* @route '/api/operator/auth/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/api/operator/auth/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Operator\AuthController::logout
* @see app/Http/Controllers/Operator/AuthController.php:46
* @route '/api/operator/auth/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\AuthController::logout
* @see app/Http/Controllers/Operator/AuthController.php:46
* @route '/api/operator/auth/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::logout
* @see app/Http/Controllers/Operator/AuthController.php:46
* @route '/api/operator/auth/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::logout
* @see app/Http/Controllers/Operator/AuthController.php:46
* @route '/api/operator/auth/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

/**
* @see \App\Http\Controllers\Operator\AuthController::updateProfile
* @see app/Http/Controllers/Operator/AuthController.php:87
* @route '/api/operator/auth/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/operator/auth/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\AuthController::updateProfile
* @see app/Http/Controllers/Operator/AuthController.php:87
* @route '/api/operator/auth/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\AuthController::updateProfile
* @see app/Http/Controllers/Operator/AuthController.php:87
* @route '/api/operator/auth/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::updateProfile
* @see app/Http/Controllers/Operator/AuthController.php:87
* @route '/api/operator/auth/profile'
*/
const updateProfileForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProfile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::updateProfile
* @see app/Http/Controllers/Operator/AuthController.php:87
* @route '/api/operator/auth/profile'
*/
updateProfileForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProfile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateProfile.form = updateProfileForm

/**
* @see \App\Http\Controllers\Operator\AuthController::changePassword
* @see app/Http/Controllers/Operator/AuthController.php:133
* @route '/api/operator/auth/password'
*/
export const changePassword = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: changePassword.url(options),
    method: 'put',
})

changePassword.definition = {
    methods: ["put"],
    url: '/api/operator/auth/password',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Operator\AuthController::changePassword
* @see app/Http/Controllers/Operator/AuthController.php:133
* @route '/api/operator/auth/password'
*/
changePassword.url = (options?: RouteQueryOptions) => {
    return changePassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Operator\AuthController::changePassword
* @see app/Http/Controllers/Operator/AuthController.php:133
* @route '/api/operator/auth/password'
*/
changePassword.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: changePassword.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::changePassword
* @see app/Http/Controllers/Operator/AuthController.php:133
* @route '/api/operator/auth/password'
*/
const changePasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changePassword.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Operator\AuthController::changePassword
* @see app/Http/Controllers/Operator/AuthController.php:133
* @route '/api/operator/auth/password'
*/
changePasswordForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changePassword.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

changePassword.form = changePasswordForm

const AuthController = { login, me, logout, updateProfile, changePassword }

export default AuthController