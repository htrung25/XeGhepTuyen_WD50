import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\AuthController::login
* @see app/Http/Controllers/Admin/AuthController.php:15
* @route '/api/admin/auth/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/api/admin/auth/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AuthController::login
* @see app/Http/Controllers/Admin/AuthController.php:15
* @route '/api/admin/auth/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AuthController::login
* @see app/Http/Controllers/Admin/AuthController.php:15
* @route '/api/admin/auth/login'
*/
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::login
* @see app/Http/Controllers/Admin/AuthController.php:15
* @route '/api/admin/auth/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::login
* @see app/Http/Controllers/Admin/AuthController.php:15
* @route '/api/admin/auth/login'
*/
loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

login.form = loginForm

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
*/
export const me = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

me.definition = {
    methods: ["get","head"],
    url: '/api/admin/auth/me',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
*/
me.url = (options?: RouteQueryOptions) => {
    return me.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
*/
me.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
*/
me.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: me.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
*/
const meForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
*/
meForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::me
* @see app/Http/Controllers/Admin/AuthController.php:66
* @route '/api/admin/auth/me'
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
* @see \App\Http\Controllers\Admin\AuthController::logout
* @see app/Http/Controllers/Admin/AuthController.php:60
* @route '/api/admin/auth/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/api/admin/auth/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AuthController::logout
* @see app/Http/Controllers/Admin/AuthController.php:60
* @route '/api/admin/auth/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AuthController::logout
* @see app/Http/Controllers/Admin/AuthController.php:60
* @route '/api/admin/auth/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::logout
* @see app/Http/Controllers/Admin/AuthController.php:60
* @route '/api/admin/auth/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::logout
* @see app/Http/Controllers/Admin/AuthController.php:60
* @route '/api/admin/auth/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

/**
* @see \App\Http\Controllers\Admin\AuthController::updateProfile
* @see app/Http/Controllers/Admin/AuthController.php:74
* @route '/api/admin/auth/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/admin/auth/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\AuthController::updateProfile
* @see app/Http/Controllers/Admin/AuthController.php:74
* @route '/api/admin/auth/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AuthController::updateProfile
* @see app/Http/Controllers/Admin/AuthController.php:74
* @route '/api/admin/auth/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::updateProfile
* @see app/Http/Controllers/Admin/AuthController.php:74
* @route '/api/admin/auth/profile'
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
* @see \App\Http\Controllers\Admin\AuthController::updateProfile
* @see app/Http/Controllers/Admin/AuthController.php:74
* @route '/api/admin/auth/profile'
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
* @see \App\Http\Controllers\Admin\AuthController::changePassword
* @see app/Http/Controllers/Admin/AuthController.php:101
* @route '/api/admin/auth/change-password'
*/
export const changePassword = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: changePassword.url(options),
    method: 'put',
})

changePassword.definition = {
    methods: ["put"],
    url: '/api/admin/auth/change-password',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\AuthController::changePassword
* @see app/Http/Controllers/Admin/AuthController.php:101
* @route '/api/admin/auth/change-password'
*/
changePassword.url = (options?: RouteQueryOptions) => {
    return changePassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AuthController::changePassword
* @see app/Http/Controllers/Admin/AuthController.php:101
* @route '/api/admin/auth/change-password'
*/
changePassword.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: changePassword.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Admin\AuthController::changePassword
* @see app/Http/Controllers/Admin/AuthController.php:101
* @route '/api/admin/auth/change-password'
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
* @see \App\Http\Controllers\Admin\AuthController::changePassword
* @see app/Http/Controllers/Admin/AuthController.php:101
* @route '/api/admin/auth/change-password'
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