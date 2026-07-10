import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/admin/admin-staff',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::index
* @see app/Http/Controllers/Admin/AdminStaffController.php:18
* @route '/api/admin/admin-staff'
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

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::store
* @see app/Http/Controllers/Admin/AdminStaffController.php:60
* @route '/api/admin/admin-staff'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/admin/admin-staff',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::store
* @see app/Http/Controllers/Admin/AdminStaffController.php:60
* @route '/api/admin/admin-staff'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::store
* @see app/Http/Controllers/Admin/AdminStaffController.php:60
* @route '/api/admin/admin-staff'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::store
* @see app/Http/Controllers/Admin/AdminStaffController.php:60
* @route '/api/admin/admin-staff'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::store
* @see app/Http/Controllers/Admin/AdminStaffController.php:60
* @route '/api/admin/admin-staff'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/admin/admin-staff/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::show
* @see app/Http/Controllers/Admin/AdminStaffController.php:50
* @route '/api/admin/admin-staff/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::update
* @see app/Http/Controllers/Admin/AdminStaffController.php:93
* @route '/api/admin/admin-staff/{id}'
*/
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/api/admin/admin-staff/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::update
* @see app/Http/Controllers/Admin/AdminStaffController.php:93
* @route '/api/admin/admin-staff/{id}'
*/
update.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return update.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::update
* @see app/Http/Controllers/Admin/AdminStaffController.php:93
* @route '/api/admin/admin-staff/{id}'
*/
update.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::update
* @see app/Http/Controllers/Admin/AdminStaffController.php:93
* @route '/api/admin/admin-staff/{id}'
*/
const updateForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::update
* @see app/Http/Controllers/Admin/AdminStaffController.php:93
* @route '/api/admin/admin-staff/{id}'
*/
updateForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::ban
* @see app/Http/Controllers/Admin/AdminStaffController.php:142
* @route '/api/admin/admin-staff/{id}/ban'
*/
export const ban = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ban.url(args, options),
    method: 'post',
})

ban.definition = {
    methods: ["post"],
    url: '/api/admin/admin-staff/{id}/ban',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::ban
* @see app/Http/Controllers/Admin/AdminStaffController.php:142
* @route '/api/admin/admin-staff/{id}/ban'
*/
ban.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return ban.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::ban
* @see app/Http/Controllers/Admin/AdminStaffController.php:142
* @route '/api/admin/admin-staff/{id}/ban'
*/
ban.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ban.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::ban
* @see app/Http/Controllers/Admin/AdminStaffController.php:142
* @route '/api/admin/admin-staff/{id}/ban'
*/
const banForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ban.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::ban
* @see app/Http/Controllers/Admin/AdminStaffController.php:142
* @route '/api/admin/admin-staff/{id}/ban'
*/
banForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ban.url(args, options),
    method: 'post',
})

ban.form = banForm

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::unban
* @see app/Http/Controllers/Admin/AdminStaffController.php:172
* @route '/api/admin/admin-staff/{id}/unban'
*/
export const unban = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unban.url(args, options),
    method: 'post',
})

unban.definition = {
    methods: ["post"],
    url: '/api/admin/admin-staff/{id}/unban',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::unban
* @see app/Http/Controllers/Admin/AdminStaffController.php:172
* @route '/api/admin/admin-staff/{id}/unban'
*/
unban.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return unban.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::unban
* @see app/Http/Controllers/Admin/AdminStaffController.php:172
* @route '/api/admin/admin-staff/{id}/unban'
*/
unban.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unban.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::unban
* @see app/Http/Controllers/Admin/AdminStaffController.php:172
* @route '/api/admin/admin-staff/{id}/unban'
*/
const unbanForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unban.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::unban
* @see app/Http/Controllers/Admin/AdminStaffController.php:172
* @route '/api/admin/admin-staff/{id}/unban'
*/
unbanForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unban.url(args, options),
    method: 'post',
})

unban.form = unbanForm

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::resetPassword
* @see app/Http/Controllers/Admin/AdminStaffController.php:190
* @route '/api/admin/admin-staff/{id}/reset-password'
*/
export const resetPassword = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/api/admin/admin-staff/{id}/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::resetPassword
* @see app/Http/Controllers/Admin/AdminStaffController.php:190
* @route '/api/admin/admin-staff/{id}/reset-password'
*/
resetPassword.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return resetPassword.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::resetPassword
* @see app/Http/Controllers/Admin/AdminStaffController.php:190
* @route '/api/admin/admin-staff/{id}/reset-password'
*/
resetPassword.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::resetPassword
* @see app/Http/Controllers/Admin/AdminStaffController.php:190
* @route '/api/admin/admin-staff/{id}/reset-password'
*/
const resetPasswordForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetPassword.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\AdminStaffController::resetPassword
* @see app/Http/Controllers/Admin/AdminStaffController.php:190
* @route '/api/admin/admin-staff/{id}/reset-password'
*/
resetPasswordForm.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.form = resetPasswordForm

const AdminStaffController = { index, store, show, update, ban, unban, resetPassword }

export default AdminStaffController