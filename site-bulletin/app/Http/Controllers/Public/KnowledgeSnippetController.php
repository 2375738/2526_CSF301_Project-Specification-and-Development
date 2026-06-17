<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\KnowledgeSnippet;
use App\Models\Link;
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

        $quickLinks = collect();
        $announcements = collect();

        if ($search !== '') {
            $quickLinks = Link::query()
                ->with('category.department:id,name')
                ->active()
                ->where(function (Builder $query) use ($search) {
                    $query->where('label', 'like', '%' . $search . '%')
                        ->orWhere('url', 'like', '%' . $search . '%')
                        ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                            ->where('name', 'like', '%' . $search . '%'));
                })
                ->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->visibleTo($user))
                ->orderBy('order')
                ->limit(8)
                ->get();

            $announcements = Announcement::query()
                ->with('department:id,name')
                ->active()
                ->visibleTo($user)
                ->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%')
                        ->orWhere('body', 'like', '%' . $search . '%');
                })
                ->ordered()
                ->limit(8)
                ->get();
        }

        return view('knowledge.index', [
            'snippets' => $snippets,
            'search' => $search,
            'quickLinks' => $quickLinks,
            'announcements' => $announcements,
        ]);
    }
}
