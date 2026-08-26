<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TemporaryLoan;
use Illuminate\Auth\Access\HandlesAuthorization;

class TemporaryLoanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_temporary::loan');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TemporaryLoan $temporaryLoan): bool
    {
        return $user->can('view_temporary::loan');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_temporary::loan');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TemporaryLoan $temporaryLoan): bool
    {
        return $user->can('update_temporary::loan');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TemporaryLoan $temporaryLoan): bool
    {
        return $user->can('delete_temporary::loan');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_temporary::loan');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, TemporaryLoan $temporaryLoan): bool
    {
        return $user->can('force_delete_temporary::loan');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_temporary::loan');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, TemporaryLoan $temporaryLoan): bool
    {
        return $user->can('restore_temporary::loan');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_temporary::loan');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, TemporaryLoan $temporaryLoan): bool
    {
        return $user->can('replicate_temporary::loan');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_temporary::loan');
    }
}
