<?php

namespace App\Services;

use App\Enums\InteractionTypeEnum;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Pagination\CursorPaginator;

class CommentService
{
    public function getComments(Post $post, ?string $search = '', int $limit = 10): CursorPaginator
    {
        return $post
            ->comments()
            ->with(['user:id,name,email,role'])
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->with([
                'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->search($search)
            ->orderBy('created_at', 'Desc')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function getAllComments(?string $search = '', int $limit = 10)
    {
        return Comment::query()
            ->with(['user:id,name,email,role'])
            ->withCount(InteractionTypeEnum::actionsInteractionsCounts())
            ->with([
                'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
            ])
            ->search($search)
            ->orderBy('created_at', 'Desc')
            ->orderBy('id')
            ->cursorPaginate($limit);
    }

    public function getComment(Comment $comment): Comment
    {
        return $this->withInteractionPresentation($comment->load('user:id,name,email,role'));
    }

    public function storeComment(Post $post, string $content): Comment
    {
        $comment = Comment::create([
            'content' => $content,
            'user_id' => auth()->id(),
            'post_id' => $post->id,
        ]);

        return $this->withInteractionPresentation($comment->load('user:id,name,email,role'));
    }

    public function updateComment(Comment $comment, string $content): Comment
    {
        $comment->content = $content;
        $comment->save();

        return $this->withInteractionPresentation($comment->load('user:id,name,email,role'));
    }

    private function withInteractionPresentation(Comment $comment): Comment
    {
        $comment->loadCount(InteractionTypeEnum::actionsInteractionsCounts());

        $comment->load([
            'interactions' => fn ($q) => $q->where('user_id', auth()->id()),
        ]);

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
