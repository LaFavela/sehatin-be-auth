<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsToMany;

class Role extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'roles';
    protected $fillable = ['name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    /**
     * Find a role by its name
     */
    public static function findByName(string $name)
    {
        return static::where('name', $name)->first();
    }

    /**
     * Assign this role to a user
     */
    public function assignToUser(User|string $user): void
    {
        if (is_string($user)) {
            $user = User::find($user);
        }

        if ($user && !$this->hasUser($user)) {
            $this->users()->attach($user->_id);
        }
    }

    /**
     * Check if role is assigned to user
     */
    public function hasUser(User|string $user): bool
    {
        if (is_string($user)) {
            $user = User::find($user);
            if (!$user) return false;
        }

        return $this->users()->where('user_id', $user->_id)->exists();
    }

    /**
     * Remove role from user
     */
    public function removeFromUser(User|string $user): void
    {
        if (is_string($user)) {
            $user = User::find($user);
        }

        if ($user) {
            $this->users()->detach($user->_id);
        }
    }
}
