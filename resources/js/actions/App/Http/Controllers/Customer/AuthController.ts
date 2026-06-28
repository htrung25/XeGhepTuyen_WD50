import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Customer\AuthController::sendOtp
* @see app/Http/Controllers/Customer/AuthController.php:60
* @route '/api/customer/auth/send-otp'
*/
export const sendOtp = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendOtp.url(options),
    method: 'post',
})

sendOtp.definition = {
    methods: ["post"],
    url: '/api/customer/auth/send-otp',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::sendOtp
* @see app/Http/Controllers/Customer/AuthController.php:60
* @route '/api/customer/auth/send-otp'
*/
sendOtp.url = (options?: RouteQueryOptions) => {
    return sendOtp.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::sendOtp
* @see app/Http/Controllers/Customer/AuthController.php:60
* @route '/api/customer/auth/send-otp'
*/
sendOtp.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sendOtp.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::sendOtp
* @see app/Http/Controllers/Customer/AuthController.php:60
* @route '/api/customer/auth/send-otp'
*/
const sendOtpForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sendOtp.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::sendOtp
* @see app/Http/Controllers/Customer/AuthController.php:60
* @route '/api/customer/auth/send-otp'
*/
sendOtpForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sendOtp.url(options),
    method: 'post',
})

sendOtp.form = sendOtpForm

/**
* @see \App\Http\Controllers\Customer\AuthController::verifyOtp
* @see app/Http/Controllers/Customer/AuthController.php:84
* @route '/api/customer/auth/verify-otp'
*/
export const verifyOtp = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verifyOtp.url(options),
    method: 'post',
})

verifyOtp.definition = {
    methods: ["post"],
    url: '/api/customer/auth/verify-otp',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::verifyOtp
* @see app/Http/Controllers/Customer/AuthController.php:84
* @route '/api/customer/auth/verify-otp'
*/
verifyOtp.url = (options?: RouteQueryOptions) => {
    return verifyOtp.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::verifyOtp
* @see app/Http/Controllers/Customer/AuthController.php:84
* @route '/api/customer/auth/verify-otp'
*/
verifyOtp.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verifyOtp.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::verifyOtp
* @see app/Http/Controllers/Customer/AuthController.php:84
* @route '/api/customer/auth/verify-otp'
*/
const verifyOtpForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: verifyOtp.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::verifyOtp
* @see app/Http/Controllers/Customer/AuthController.php:84
* @route '/api/customer/auth/verify-otp'
*/
verifyOtpForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: verifyOtp.url(options),
    method: 'post',
})

verifyOtp.form = verifyOtpForm

/**
* @see \App\Http\Controllers\Customer\AuthController::register
* @see app/Http/Controllers/Customer/AuthController.php:104
* @route '/api/customer/auth/register'
*/
export const register = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(options),
    method: 'post',
})

register.definition = {
    methods: ["post"],
    url: '/api/customer/auth/register',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::register
* @see app/Http/Controllers/Customer/AuthController.php:104
* @route '/api/customer/auth/register'
*/
register.url = (options?: RouteQueryOptions) => {
    return register.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::register
* @see app/Http/Controllers/Customer/AuthController.php:104
* @route '/api/customer/auth/register'
*/
register.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: register.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::register
* @see app/Http/Controllers/Customer/AuthController.php:104
* @route '/api/customer/auth/register'
*/
const registerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: register.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::register
* @see app/Http/Controllers/Customer/AuthController.php:104
* @route '/api/customer/auth/register'
*/
registerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: register.url(options),
    method: 'post',
})

register.form = registerForm

/**
* @see \App\Http\Controllers\Customer\AuthController::login
* @see app/Http/Controllers/Customer/AuthController.php:170
* @route '/api/customer/auth/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

login.definition = {
    methods: ["post"],
    url: '/api/customer/auth/login',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::login
* @see app/Http/Controllers/Customer/AuthController.php:170
* @route '/api/customer/auth/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::login
* @see app/Http/Controllers/Customer/AuthController.php:170
* @route '/api/customer/auth/login'
*/
login.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::login
* @see app/Http/Controllers/Customer/AuthController.php:170
* @route '/api/customer/auth/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::login
* @see app/Http/Controllers/Customer/AuthController.php:170
* @route '/api/customer/auth/login'
*/
loginForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: login.url(options),
    method: 'post',
})

login.form = loginForm

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
*/
export const me = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

me.definition = {
    methods: ["get","head"],
    url: '/api/customer/auth/me',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
*/
me.url = (options?: RouteQueryOptions) => {
    return me.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
*/
me.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
*/
me.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: me.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
*/
const meForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
*/
meForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: me.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::me
* @see app/Http/Controllers/Customer/AuthController.php:206
* @route '/api/customer/auth/me'
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
* @see \App\Http\Controllers\Customer\AuthController::updateProfile
* @see app/Http/Controllers/Customer/AuthController.php:214
* @route '/api/customer/auth/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/customer/auth/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::updateProfile
* @see app/Http/Controllers/Customer/AuthController.php:214
* @route '/api/customer/auth/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::updateProfile
* @see app/Http/Controllers/Customer/AuthController.php:214
* @route '/api/customer/auth/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::updateProfile
* @see app/Http/Controllers/Customer/AuthController.php:214
* @route '/api/customer/auth/profile'
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
* @see \App\Http\Controllers\Customer\AuthController::updateProfile
* @see app/Http/Controllers/Customer/AuthController.php:214
* @route '/api/customer/auth/profile'
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
* @see \App\Http\Controllers\Customer\AuthController::changePassword
* @see app/Http/Controllers/Customer/AuthController.php:238
* @route '/api/customer/auth/change-password'
*/
export const changePassword = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: changePassword.url(options),
    method: 'post',
})

changePassword.definition = {
    methods: ["post"],
    url: '/api/customer/auth/change-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::changePassword
* @see app/Http/Controllers/Customer/AuthController.php:238
* @route '/api/customer/auth/change-password'
*/
changePassword.url = (options?: RouteQueryOptions) => {
    return changePassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::changePassword
* @see app/Http/Controllers/Customer/AuthController.php:238
* @route '/api/customer/auth/change-password'
*/
changePassword.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: changePassword.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::changePassword
* @see app/Http/Controllers/Customer/AuthController.php:238
* @route '/api/customer/auth/change-password'
*/
const changePasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changePassword.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::changePassword
* @see app/Http/Controllers/Customer/AuthController.php:238
* @route '/api/customer/auth/change-password'
*/
changePasswordForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: changePassword.url(options),
    method: 'post',
})

changePassword.form = changePasswordForm

/**
* @see \App\Http\Controllers\Customer\AuthController::logout
* @see app/Http/Controllers/Customer/AuthController.php:200
* @route '/api/customer/auth/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/api/customer/auth/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Customer\AuthController::logout
* @see app/Http/Controllers/Customer/AuthController.php:200
* @route '/api/customer/auth/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Customer\AuthController::logout
* @see app/Http/Controllers/Customer/AuthController.php:200
* @route '/api/customer/auth/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::logout
* @see app/Http/Controllers/Customer/AuthController.php:200
* @route '/api/customer/auth/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Customer\AuthController::logout
* @see app/Http/Controllers/Customer/AuthController.php:200
* @route '/api/customer/auth/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

const AuthController = { sendOtp, verifyOtp, register, login, me, updateProfile, changePassword, logout }

export default AuthController