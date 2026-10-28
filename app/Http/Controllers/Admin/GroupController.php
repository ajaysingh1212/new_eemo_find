<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(): View
    {
        $groups = Group::query()
            ->with('creator')
            ->latest()
            ->paginate(15);

        $memberIds = $groups->getCollection()
            ->flatMap(fn (Group $group) => $group->group_members ?? [])
            ->unique()
            ->values();
        $usersById = User::query()
            ->whereIn('id', $memberIds)
            ->get(['id', 'name', 'user_code'])
            ->keyBy('id');

        $groups->getCollection()->each(function (Group $group) use ($usersById): void {
            $group->setAttribute(
                'member_users',
                $usersById->only($group->group_members ?? [])->values()
            );
        });

        $users = User::query()->orderBy('name')->get(['id', 'name', 'user_code']);
        $availableCreators = User::query()
            ->whereNotIn('id', Group::query()->select('created_by'))
            ->orderBy('name')
            ->get(['id', 'name', 'user_code']);

        return view('admin.groups.index', [
            'groups' => $groups,
            'users' => $users,
            'availableCreators' => $availableCreators,
            'totalCount' => Group::count(),
            'activeCount' => Group::where('status', 'active')->count(),
            'inactiveCount' => Group::where('status', 'inactive')->count(),
            'blockedCount' => Group::where('status', 'blocked')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'created_by' => [
                'required',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('groups', 'created_by'),
            ],
            'group_members' => ['required', 'array', 'min:1'],
            'group_members.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')],
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
        ], [
            'created_by.unique' => 'This user has already created a group.',
            'group_members.required' => 'Select at least one group member.',
            'group_members.min' => 'Select at least one group member.',
        ]);

        Group::create($validated);

        return redirect()->route('admin.groups')->with('status', 'Group created successfully.');
    }
}
