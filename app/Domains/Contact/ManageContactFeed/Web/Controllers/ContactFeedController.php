<?php

namespace App\Domains\Contact\ManageContactFeed\Web\Controllers;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\ModuleFeedViewHelper;
use App\Helpers\PaginatorHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Post;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactFeedController extends Controller
{
    public function show(Request $request, string $vaultId, string $contactId)
    {
        $perPage = 100;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        // ---------- Feed Items Query using Eloquent (converted to a query builder) ----------
        $feedQuery = ContactFeedItem::query()
            ->leftJoin('users', 'contact_feed_items.author_id', '=', 'users.id')
            ->where('contact_feed_items.contact_id', $contactId)
            ->select([
                DB::raw("'feed' as type"),
                'contact_feed_items.id',
                'contact_feed_items.contact_id',
                'contact_feed_items.created_at',
                'contact_feed_items.action',
                'contact_feed_items.description',
                'contact_feed_items.author_id',
                'contact_feed_items.feedable_id',
                'contact_feed_items.feedable_type',
                DB::raw('NULL as title'),
                DB::raw('NULL as content'),
            ])
            ->getQuery(); // Convert to Query\Builder

        // ---------- Posts Query using Eloquent (converted to a query builder) ----------
        $postsQuery = Post::query()
            ->join('contact_post', 'posts.id', '=', 'contact_post.post_id')
            ->leftJoin('post_sections', 'posts.id', '=', 'post_sections.post_id')
            ->where('contact_post.contact_id', $contactId)
            ->where('posts.published', 1) // Adjust if necessary
            ->groupBy('posts.id', 'contact_post.contact_id', 'posts.created_at', 'posts.title')
            ->select([
                DB::raw("'post' as type"),
                'posts.id',
                'contact_post.contact_id',
                'posts.created_at',
                DB::raw('NULL as action'),
                DB::raw('NULL as description'),
                DB::raw('NULL as author_id'),
                DB::raw('NULL as feedable_id'),
                DB::raw('NULL as feedable_type'),
                'posts.title',
                DB::raw("GROUP_CONCAT(post_sections.content ORDER BY post_sections.id SEPARATOR ' ') as content"),
            ])
            ->getQuery(); // Convert to Query\Builder

        // ---------- Combine the two queries using UNION ALL ----------
        $combinedQuery = $feedQuery->unionAll($postsQuery);

        // ---------- Build the full SQL for ordering and pagination ----------
        $unionSql = $combinedQuery->toSql();
        $bindings = $combinedQuery->getBindings();
        $sql = "SELECT * FROM ({$unionSql}) AS combined ORDER BY created_at DESC LIMIT {$offset}, {$perPage}";

        // Execute the query using DB::select()
        $items = DB::select($sql, $bindings);
        $items = collect($items)->map(fn ($item) => (array) $item);

        // ---------- Total Count ----------
        $countSql = "SELECT COUNT(*) as total FROM ({$unionSql}) AS combined";
        $countResult = DB::select($countSql, $bindings);
        $total = $countResult[0]->total ?? 0;

        // ---------- Retrieve vault details ----------
        $vault = Vault::find($vaultId);

        // ---------- Prepare Pagination ----------
        $paginator = PaginatorHelper::getData(new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        ));

        $contact = Contact::find($contactId);

        $itemsCollection = collect($items)->map(function ($item) use ($contact) {
            if ($item['type'] === 'feed') {
                // Convert the raw array into a ContactFeedItem object.
                $contactFeedItem = new ContactFeedItem;
                $contactFeedItem->id = $item['id'];
                $contactFeedItem->author_id = $item['author_id'];
                $contactFeedItem->action = $item['action'];
                $contactFeedItem->description = $item['description'];
                $contactFeedItem->created_at = $item['created_at'];
                $contactFeedItem->contact = $contact;

                return $contactFeedItem;
            }

            if ($item['type'] === 'post') {
                // Convert the raw array into a Post object.
                $post = new Post;
                $post->id = $item['id'];
                $post->title = $item['title'];
                $post->content = $item['content'];
                $post->created_at = Carbon::parse($item['created_at']);

                return $post;
            }

            return null;
        });

        return response()->json([
            'data' => ModuleFeedViewHelper::data($itemsCollection, Auth::user(), $vault),
            'paginator' => $paginator,
        ], 200);
    }
}
