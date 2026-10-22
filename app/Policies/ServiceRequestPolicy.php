<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function view(User $user, ServiceRequest $request): bool
    {
        return $user->isAdmin() || $request->user_id === $user->id;
    }

    /** Applicant actions: pay, cancel, respond to an info request. */
    public function update(User $user, ServiceRequest $request): bool
    {
        return $request->user_id === $user->id;
    }

    /** Back-office actions: change status, review documents, add notes. */
    public function process(User $user, ServiceRequest $request): bool
    {
        return $user->isAdmin();
    }
}
