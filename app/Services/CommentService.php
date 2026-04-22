<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Pagination\CursorPaginator;

class CommentService
{
    public function getComments(Post $post, ?string $search = '', int $limit = 10): CursorPaginator
    {
        return $post
            ->comments()
            ->with(['user:id,name'])
            ->search($search)
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function storeComment(Post $post, string $content): Comment
    {
        $comment = Comment::create([
            'content' => $content,
            'user_id' => auth()->id(),
            'post_id' => $post->id,
        ]);

        return $comment->load('user:id,name');
    }

    public function updateComment(Comment $comment, string $content): Comment
    {
        $comment->content = $content;
        $comment->save();

        return $comment->load('user:id,name');
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
