import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * UI permission gating mirroring spatie/laravel-permission on the client.
 * Super admins implicitly pass every check, matching Gate::before behavior.
 */
export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);

    function can(permission) {
        if (!user.value) {
            return false;
        }

        if (user.value.is_super_admin) {
            return true;
        }

        return (user.value.permissions || []).includes(permission);
    }

    function hasRole(role) {
        return (user.value?.roles || []).includes(role);
    }

    return { user, can, hasRole };
}
