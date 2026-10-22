<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $tickets = SupportTicket::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($ticketQuery) use ($search) {
                    $ticketQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhere('issue', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('createdBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('user_code', 'like', "%{$search}%"));
                });
            })
            ->with('createdBy')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.support.index', [
            'tickets' => $tickets,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'user_code']),
            'search' => $search,
            'totalCount' => SupportTicket::count(),
            'pendingCount' => SupportTicket::where('status', 'pending')->count(),
            'resolvedCount' => SupportTicket::where('status', 'resolved')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:30'],
            'issue' => ['required', 'string', 'max:5000'],
            'created_by_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        SupportTicket::create([
            ...$validated,
            'created_by_user_id' => $validated['created_by_user_id'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.support')->with('status', 'Support ticket created.');
    }

    public function update(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['pending', 'resolved', 'rejected'])],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.support', [...$request->query(), 'ticket' => $supportTicket->id])
                ->withErrors($validator)
                ->withInput();
        }

        $supportTicket->update($validator->validated());

        return redirect()->route('admin.support')->with('status', 'Support ticket updated.');
    }
}
