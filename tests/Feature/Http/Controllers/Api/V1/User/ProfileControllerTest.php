<?php

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

describe('profile with authentication', function () {
    beforeEach(function () {
        $this->user = CreateUserAs(RoleEnum::USER);
        Sanctum::actingAs($this->user);
    });

    describe('profile show', function () {
        it('returns 200 with profile and count attributes', function () {
            $response = $this->getJson(route('api.user.profile.show', $this->user->id))
                ->assertOk();

            $data = $response->json('data');

            expect($data['id'])->toBe($this->user->id)
                ->and($data)->toHaveKeys(['posts_count', 'viewed_posts_count', 'followers_count', 'followings_count']);
        });
    });

    describe('profile update', function () {
        it('returns 422 when name, email, and avatar are all omitted', function () {
            $this->putJson(route('api.user.profile.update'), [])
                ->assertUnprocessable();
        });

        it('returns 200 and updates name', function () {
            $this->putJson(route('api.user.profile.update'), [
                'name' => 'Updated Display Name Here',
            ])
                ->assertOk()
                ->assertJsonPath('data.name', 'Updated Display Name Here');

            expect($this->user->fresh()->name)->toBe('Updated Display Name Here');
        });

        it('returns 200 and updates email', function () {
            $newEmail = 'newunique-profile@example.com';

            $this->putJson(route('api.user.profile.update'), [
                'email' => $newEmail,
            ])
                ->assertOk()
                ->assertJsonPath('data.email', $newEmail);

            expect($this->user->fresh()->email)->toBe($newEmail);
        });

        it('returns 422 when email belongs to another user', function () {
            $other = User::factory()->create();

            $this->putJson(route('api.user.profile.update'), [
                'email' => $other->email,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['email']);
        });

        it('returns 200 and stores avatar', function () {
            $this->put(route('api.user.profile.update'), [
                'avatar' => UploadedFile::fake()->image('face.jpg'),
            ], ['Accept' => 'application/json'])
                ->assertOk();

            expect($this->user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
        });
    });

    describe('profile password', function () {
        it('returns 204 and changes password when current password matches', function () {
            $this->putJson(route('api.user.profile.password'), [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
                ->assertNoContent();

            expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
        });

        it('returns 422 when current password is wrong', function () {
            $this->putJson(route('api.user.profile.password'), [
                'current_password' => 'not-the-right-password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['current_password']);
        });

        it('returns 422 when password confirmation does not match', function () {
            $this->putJson(route('api.user.profile.password'), [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'different',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        });
    });

});

describe('profile without authentication', function () {
    it('returns 401 for profile show', function () {

        $this->getJson(route('api.user.profile.show', CreateUserAs(RoleEnum::USER)->id))
            ->assertUnauthorized();
    });

    it('returns 401 for profile update', function () {
        $this->putJson(route('api.user.profile.update'), [
            'name' => 'Anyone',
        ])
            ->assertUnauthorized();
    });

    it('returns 401 for password change', function () {
        $this->putJson(route('api.user.profile.password'), [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertUnauthorized();
    });
});
