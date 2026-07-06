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
            'data' => $contacts->getCollection()->map(fn (DirectoryContact $c) => [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'full_name' => $c->fullName(),
                'phone' => $c->phone,
                'description' => $c->description,
            ]),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }
}
