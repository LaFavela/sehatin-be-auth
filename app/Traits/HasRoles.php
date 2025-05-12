<?php

namespace app\Traits;

use App\Models\Role;
use MongoDB\Laravel\Relations\BelongsToMany;

trait HasRoles
{
    /**
     * Get all roles assigned to the user
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**\
     * Assign a role to the user
     */
    public function assignRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role && !$this->hasRole($role)) {
            $this->roles()->attach($role->_id);
        }
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasRole(Role|string|array $roles): bool
    {
        if (is_string($roles)) {
            return $this->roles()->where('name', $roles)->exists();
        }

        if ($roles instanceof Role) {
            return $this->roles()->where('role_id', $roles->_id)->exists();
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }

    /**
     * Check if user has all the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Remove role from user
     */
    public function removeRole(Role|string $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->_id);
        }
    }

    /**
     * Remove all roles from user
     */
    public function removeAllRoles(): void
    {
        $this->roles()->detach();
    }

    /**
     * Sync user roles
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('name', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->_id;
                }
            } elseif ($role instanceof Role) {
                $roleIds[] = $role->_id;
            }
        }

        $this->roles()->sync($roleIds);
    }
}
