<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeSnippet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeSnippetController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));

        $snippets = KnowledgeSnippet::query()
            ->with('department:id,name')
            ->active()
            ->visibleTo($user)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', '%' . $search . '%')
                        ->orWhere('summary', 'like', '%' . $search . '%')
                        ->orWhere('body', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        return view('knowledge.index', [
            'snippets' => $snippets,
            'search' => $search,
        ]);
    }
}
