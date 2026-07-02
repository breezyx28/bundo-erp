<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use App\Http\Requests\UserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\Branch\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use InteractsWithToast;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        return Inertia::render('Users/Index', [
            'users' => User::query()
                ->with('roles:id,name')
                ->when($search, fn ($q) => $q->where(fn ($qq) => $qq
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_active' => (bool) $user->is_active,
                    'role' => $user->roles->first()?->name ?? 'salesperson',
                    'branchIds' => $user->branches()->pluck('branches.id')->all(),
                ]),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->whereNotIn('name', ['super_admin'])->orderBy('name')->pluck('name')->all(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->persist($request->validated());

        $this->toastSuccess(__('users.saved'));

        return redirect()->route('users.index');
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->id === Auth::id() && ! $data['is_active']) {
            $this->toastError(__('users.cannot_deactivate_self'));

            return redirect()->route('users.index');
        }

        $this->persist($data, $user);

        $this->toastSuccess(__('users.saved'));

        return redirect()->route('users.index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data, ?User $user = null): void
    {
        $tenantId = app(BranchContext::class)->currentTenantId();

        $payload = [
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'],
            'default_branch_id' => $data['branchIds'][0] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($user) {
            $user->update($payload);
        } else {
            $user = User::create($payload);
        }

        $user->syncRoles([$data['role']]);
        $user->branches()->sync(
            collect($data['branchIds'])
                ->mapWithKeys(fn ($id) => [$id => ['is_primary' => $id === ($data['branchIds'][0] ?? null)]])
                ->all(),
        );
    }
}
