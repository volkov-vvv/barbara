import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types/auth';

export type RoleName = User['role'];

export type UseAuthReturn = {
    user: ComputedRef<User | null>;
    roles: ComputedRef<string[]>;
    permissions: ComputedRef<string[]>;
    isAuthenticated: ComputedRef<boolean>;
    can: (permission: string) => boolean;
    hasRole: (role: RoleName | string) => boolean;
    hasAnyRole: (...roles: Array<RoleName | string>) => boolean;
};

/**
 * Auth helpers backed by Inertia shared props (`auth.user.roles`, `auth.user.permissions`).
 */
export function useAuth(): UseAuthReturn {
    const page = usePage();

    const user = computed(() => page.props.auth.user);

    const roles = computed(() => user.value?.roles ?? []);

    const permissions = computed(() => user.value?.permissions ?? []);

    const isAuthenticated = computed(() => user.value !== null);

    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function hasRole(role: RoleName | string): boolean {
        return roles.value.includes(role);
    }

    function hasAnyRole(...checkRoles: Array<RoleName | string>): boolean {
        return checkRoles.some((role) => hasRole(role));
    }

    return {
        user,
        roles,
        permissions,
        isAuthenticated,
        can,
        hasRole,
        hasAnyRole,
    };
}

/**
 * Same as `useAuth`, but asserts an authenticated user (for auth-only layouts/pages).
 */
export function useAuthenticatedUser(): ComputedRef<User> {
    const { user } = useAuth();

    return computed(() => {
        if (user.value === null) {
            throw new Error('Authenticated user is required.');
        }

        return user.value;
    });
}
