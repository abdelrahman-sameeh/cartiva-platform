<?php

namespace App\Http\Controllers\Web;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserRoleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = "%{$search}%";

                $query->where(function ($query) use ($like) {
                    $query->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.roles', [
            'users' => $users,
            'roles' => Role::query()->orderBy('id')->get(),
            'search' => $search,
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        $roleIds = collect($request->validated('role_ids'))->map(fn ($id) => (int) $id);

        DB::transaction(function () use ($user, $roleIds) {
            $adminRole = Role::query()
                ->where('name', RoleName::ADMIN->value)
                ->lockForUpdate()
                ->firstOrFail();
            $removingAdmin = $user->hasRole(RoleName::ADMIN) && ! $roleIds->contains($adminRole->id);

            if ($removingAdmin && $adminRole->users()->count() <= 1) {
                throw ValidationException::withMessages([
                    "roles.{$user->id}" => 'لا يمكن إزالة صلاحية المدير من آخر Admin في النظام.',
                ]);
            }

            $user->roles()->sync($roleIds);
        });

        return back()->with('success', "تم تحديث صلاحيات {$user->first_name} {$user->last_name} بنجاح.");
    }
}
