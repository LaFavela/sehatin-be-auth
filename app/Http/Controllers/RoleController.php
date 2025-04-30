<?php

namespace App\Http\Controllers;

use app\Http\Requests\RoleRequest;
use app\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController
{
    /**
     * Assign a specific role to a user.
     *
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function assignRole(RoleRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        $validated = $request->validated();

        try {
            $user = User::findOrFail($validated['user_id']);

            $role = Role::findByName($validated['role'])->firstOrFail();

            $user->assignRole($role);

            return (new MessageResource(new UserResource($role), true, 'Assign role successful'))->response()->setStatusCode(200);
        } catch (ModelNotFoundException $e) {
            return (new MessageResource(null, false, 'User or Role not found.'))->response()->setStatusCode(404); // 404 Not Found)
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to assign role', $e->getMessage()))->response()->setStatusCode(500);
        }
    }

    /**
     * Remove a specific role from a user.
     *
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function removeRole(RoleRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        $validated = $request->validated();

        try {
            $user = User::findOrFail($validated['user_id']);

            $role = Role::where('name', $validated['role'])->firstOrFail();

            if (!$user->hasRole($role)) {
                return (new MessageResource(null, false, 'User has no role'))->response()->setStatusCode(404);
            }

            $user->removeRole($role);

            return (new MessageResource(new UserResource($user), true, 'Remove role successful'))->response()->setStatusCode(200);
        } catch (ModelNotFoundException $e) {
            return (new MessageResource(null, false, 'User or Role not found.'))->response()->setStatusCode(404); //
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to remove role', $e->getMessage()))->response()->setStatusCode(500);
        }
    }
}
