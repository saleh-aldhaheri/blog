<?php

use App\Enums\RoleEnum;
use App\Models\Comment;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseMissing;


beforeEach(function () {
    $this->admin = CreateUserAs(RoleEnum::ADMIN);
    $this->user = CreateUserAs(RoleEnum::USER);
    Sanctum::actingAs($this->admin);
});

describe('comment index', function () {
    it('returns 200 and cursor-paginated comments', function () {

        Comment::factory(4)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('api.admin.comments.index'))
            ->assertOk();

        $items = $response->json('data');

        expect($items)->toHaveCount(4)
            ->and(collect($items)->first())->toHaveKeys([
                'id',
                'content',
                'user',
                'interaction_counts',
                'created_at',
                'updated_at',
            ]);
    });

    it('returns 200 and only comments matching search in content', function () {

        $needle = 'UniqueSearchMarkerXy9';

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Noise one',
        ]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'content' => "Contains {$needle} here",
        ]);

        $url = route('api.admin.comments.index') . '?search=' . urlencode($needle);

        $response = $this->getJson($url)->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.content'))->toContain($needle);
    });

    it('respects the limit query parameter', function () {

        Comment::factory(8)->create([
            'user_id' => $this->user->id,
        ]);

        $url = route('api.admin.comments.index') . '?limit=3';

        $response = $this->getJson($url)->assertOk();

        expect($response->json('data'))->toHaveCount(3);
    });
});

describe('comment Show', function () {
    it('should show the requested comment', function () {
        $comment = Comment::factory(1)->create([
            'user_id' => $this->user->id,
        ])->first();

        $this->getJson(route('api.admin.categories.show', $comment->id))
            ->assertOk();
    });
});

describe('comment update', function () {
    it('returns 200 and updates the comment when the user is the author', function () {

        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Original comment text that is long enough for rules.',
        ]);

        $newBody = 'Updated comment body with enough characters to pass validation here.';

        $this->putJson(route('api.admin.comments.update', $comment), [
            'comment' => $newBody,
        ])->assertOk()
            ->assertJsonPath('data.content', $newBody);
    });

    it('returns 422 when comment is invalid', function () {

        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->putJson(route('api.admin.comments.update', $comment), [
            'comment' => 'x',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    });
});

describe('comment destroy', function () {
    it('returns 204 and deletes the comment when the user is the author', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->deleteJson(route('api.admin.comments.destroy', $comment))
            ->assertNoContent();

        assertDatabaseMissing('comments', ['id' => $comment->id]);
    });
});
