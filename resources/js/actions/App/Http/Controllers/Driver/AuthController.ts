import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Driver\AuthController::register
* @see app/Http/Controllers/Driver/AuthController.php:20
* @route '/api/driver/auth/register'
*/
export const register = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(options),
    method: 'post',
})

register.definition = {
    methods: ["post"],
    url: '/api/driver/auth/register',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::register
* @see app/Http/Controllers/Driver/AuthController.php:20
* @route '/api/driver/auth/register'
*/
register.url = (options?: RouteQueryOptions) => {
    return register.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::register
* @see app/Http/Controllers/Driver/AuthController.php:20
* @route '/api/driver/auth/register'
*/
register.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::register
* @see app/Http/Controllers/Driver/AuthController.php:20
* @route '/api/driver/auth/register'
*/
const registerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: register.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::register
* @see app/Http/Controllers/Driver/AuthController.php:20
* @route '/api/driver/auth/register'
*/
registerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: register.url(options),
    method: 'post',
})

register.form = registerForm

/**
* @see \App\Http\Controllers\Driver\AuthController::login
* @see app/Http/Controllers/Driver/AuthController.php:59
* @route '/api/driver/auth/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/api/driver/auth/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::login
* @see app/Http/Controllers/Driver/AuthController.php:59
* @route '/api/driver/auth/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::login
* @see app/Http/Controllers/Driver/AuthController.php:59
* @route '/api/driver/auth/login'
*/
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::login
* @see app/Http/Controllers/Driver/AuthController.php:59
* @route '/api/driver/auth/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::login
* @see app/Http/Controllers/Driver/AuthController.php:59
* @route '/api/driver/auth/login'
*/
loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

login.form = loginForm

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
*/
export const me = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

me.definition = {
    methods: ["get","head"],
    url: '/api/driver/auth/me',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
*/
me.url = (options?: RouteQueryOptions) => {
    return me.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
*/
me.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
*/
me.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: me.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
*/
const meForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
*/
meForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::me
* @see app/Http/Controllers/Driver/AuthController.php:95
* @route '/api/driver/auth/me'
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
* @see \App\Http\Controllers\Driver\AuthController::logout
* @see app/Http/Controllers/Driver/AuthController.php:88
* @route '/api/driver/auth/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/api/driver/auth/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::logout
* @see app/Http/Controllers/Driver/AuthController.php:88
* @route '/api/driver/auth/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::logout
* @see app/Http/Controllers/Driver/AuthController.php:88
* @route '/api/driver/auth/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::logout
* @see app/Http/Controllers/Driver/AuthController.php:88
* @route '/api/driver/auth/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::logout
* @see app/Http/Controllers/Driver/AuthController.php:88
* @route '/api/driver/auth/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

/**
* @see \App\Http\Controllers\Driver\AuthController::updateStatus
* @see app/Http/Controllers/Driver/AuthController.php:132
* @route '/api/driver/auth/status'
*/
export const updateStatus = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(options),
    method: 'put',
})

updateStatus.definition = {
    methods: ["put"],
    url: '/api/driver/auth/status',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::updateStatus
* @see app/Http/Controllers/Driver/AuthController.php:132
* @route '/api/driver/auth/status'
*/
updateStatus.url = (options?: RouteQueryOptions) => {
    return updateStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::updateStatus
* @see app/Http/Controllers/Driver/AuthController.php:132
* @route '/api/driver/auth/status'
*/
updateStatus.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::updateStatus
* @see app/Http/Controllers/Driver/AuthController.php:132
* @route '/api/driver/auth/status'
*/
const updateStatusForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::updateStatus
* @see app/Http/Controllers/Driver/AuthController.php:132
* @route '/api/driver/auth/status'
*/
updateStatusForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateStatus.form = updateStatusForm

/**
* @see \App\Http\Controllers\Driver\AuthController::updateProfile
* @see app/Http/Controllers/Driver/AuthController.php:144
* @route '/api/driver/auth/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/driver/auth/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::updateProfile
* @see app/Http/Controllers/Driver/AuthController.php:144
* @route '/api/driver/auth/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::updateProfile
* @see app/Http/Controllers/Driver/AuthController.php:144
* @route '/api/driver/auth/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::updateProfile
* @see app/Http/Controllers/Driver/AuthController.php:144
* @route '/api/driver/auth/profile'
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
* @see \App\Http\Controllers\Driver\AuthController::updateProfile
* @see app/Http/Controllers/Driver/AuthController.php:144
* @route '/api/driver/auth/profile'
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
* @see \App\Http\Controllers\Driver\AuthController::changePassword
* @see app/Http/Controllers/Driver/AuthController.php:177
* @route '/api/driver/auth/password'
*/
export const changePassword = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: changePassword.url(options),
    method: 'put',
})

changePassword.definition = {
    methods: ["put"],
    url: '/api/driver/auth/password',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::changePassword
* @see app/Http/Controllers/Driver/AuthController.php:177
* @route '/api/driver/auth/password'
*/
changePassword.url = (options?: RouteQueryOptions) => {
    return changePassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::changePassword
* @see app/Http/Controllers/Driver/AuthController.php:177
* @route '/api/driver/auth/password'
*/
changePassword.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: changePassword.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::changePassword
* @see app/Http/Controllers/Driver/AuthController.php:177
* @route '/api/driver/auth/password'
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
* @see \App\Http\Controllers\Driver\AuthController::changePassword
* @see app/Http/Controllers/Driver/AuthController.php:177
* @route '/api/driver/auth/password'
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

/**
* @see \App\Http\Controllers\Driver\AuthController::uploadDocument
* @see app/Http/Controllers/Driver/AuthController.php:211
* @route '/api/driver/documents'
*/
export const uploadDocument = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadDocument.url(options),
    method: 'post',
})

uploadDocument.definition = {
    methods: ["post"],
    url: '/api/driver/documents',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Driver\AuthController::uploadDocument
* @see app/Http/Controllers/Driver/AuthController.php:211
* @route '/api/driver/documents'
*/
uploadDocument.url = (options?: RouteQueryOptions) => {
    return uploadDocument.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Driver\AuthController::uploadDocument
* @see app/Http/Controllers/Driver/AuthController.php:211
* @route '/api/driver/documents'
*/
uploadDocument.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadDocument.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::uploadDocument
* @see app/Http/Controllers/Driver/AuthController.php:211
* @route '/api/driver/documents'
*/
const uploadDocumentForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadDocument.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Driver\AuthController::uploadDocument
* @see app/Http/Controllers/Driver/AuthController.php:211
* @route '/api/driver/documents'
*/
uploadDocumentForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadDocument.url(options),
    method: 'post',
})

uploadDocument.form = uploadDocumentForm

const AuthController = { register, login, me, logout, updateStatus, updateProfile, changePassword, uploadDocument }

export default AuthController