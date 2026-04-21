<?php

use App\Data\UpdateProfileData;
use App\Enums\PostStatusEnum;
use App\Enums\RoleEnum;
use App\Exceptions\BusinessException;
use App\Models\Post;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = CreateUserAs(RoleEnum::USER);
    $this->actingAs($this->user);
    $this->profileService = new ProfileService;
});

describe('getProfile', function () {
    it('returns the authenticated user with counts and avatar relation loaded', function () {
        Post::factory(2)->create(['user_id' => $this->user->id]);

        $fan = User::factory()->create();
        $fan->followings()->attach($this->user->id);

        $viewer = User::factory()->create();
        $published = Post::factory()->create([
            'user_id' => $viewer->id,
            'status' => PostStatusEnum::PUBLISHED,
        ]);
        $this->user->viewedPosts()->attach($published->id);

        $profile = $this->profileService->getProfile();

        expect($profile)->toBeInstanceOf(User::class)
            ->and($profile->id)->toBe($this->user->id)
            ->and($profile->posts_count)->toBe(2)
            ->and($profile->followers_count)->toBe(1)
            ->and($profile->viewed_posts_count)->toBe(1)
            ->and($profile->relationLoaded('avatar'))->toBeTrue();
    });
});

describe('updateProfile', function () {
    it('throws when name, email, and avatar are all absent', function () {
        $data = UpdateProfileData::from([]);

        expect(fn () => $this->profileService->updateProfile($data))
            ->toThrow(BusinessException::class, 'Provide at least one of: name, email, or avatar.');
    });

    it('updates name and returns profile with counts loaded', function () {
        $data = UpdateProfileData::from([
            'name' => 'Service Updated Name Here',
        ]);

        $result = $this->profileService->updateProfile($data);

        expect($result->name)->toBe('Service Updated Name Here')
            ->and($this->user->fresh()->name)->toBe('Service Updated Name Here')
            ->and($result)->toHaveKey('posts_count');
    });

    it('updates email', function () {
        $email = 'profile-service-'.uniqid().'@example.com';

        $result = $this->profileService->updateProfile(UpdateProfileData::from([
            'email' => $email,
        ]));

        expect($result->email)->toBe($email)
            ->and($this->user->fresh()->email)->toBe($email);
    });

    it('stores avatar on the user', function () {
        $this->profileService->updateProfile(UpdateProfileData::from([
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ]));

        expect($this->user->fresh()->getFirstMedia('avatar'))->not->toBeNull();
    });
});

describe('changePassword', function () {
    it('replaces the password hash', function () {
        $this->profileService->changePassword($this->user, 'new-secret-password');

        expect(Hash::check('new-secret-password', $this->user->fresh()->password))->toBeTrue()
            ->and(Hash::check('password', $this->user->fresh()->password))->toBeFalse();
    });
});
