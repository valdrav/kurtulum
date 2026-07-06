<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use App\Models\DirectoryContact;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(external_api()->allows('directory'), 403);

        $contacts = DirectoryContact::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(min((int) $request->input('per_page', 50), 100));

        return response()->json([
            'data' => $contacts->getCollection()->map(fn (DirectoryContact $c) => $this->payload($c)),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }

    public function show(int $contact)
    {
        abort_unless(external_api()->allows('directory'), 403);

        $record = DirectoryContact::query()->findOrFail($contact);

        return response()->json(['data' => $this->payload($record)]);
    }

    public function store(Request $request)
    {
        abort_unless(external_api()->allows('edit_directory'), 403);

        $validated = $this->validateContact($request);
        $record = DirectoryContact::create($validated);

        return response()->json(['data' => $this->payload($record)], 201);
    }

    public function update(Request $request, int $contact)
    {
        abort_unless(external_api()->allows('edit_directory'), 403);

        $record = DirectoryContact::query()->findOrFail($contact);
        $record->update($this->validateContact($request));

        return response()->json(['data' => $this->payload($record)]);
    }

    public function destroy(int $contact)
    {
        abort_unless(external_api()->allows('edit_directory'), 403);

        DirectoryContact::query()->findOrFail($contact)->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    protected function validateContact(Request $request): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
        ]);
    }

    protected function payload(DirectoryContact $c): array
    {
        return [
            'id' => $c->id,
            'first_name' => $c->first_name,
            'last_name' => $c->last_name,
            'full_name' => $c->fullName(),
            'phone' => $c->phone,
            'description' => $c->description,
        ];
    }
}
