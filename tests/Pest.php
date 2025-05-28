<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Mockery\MockInterface;

// Unit tests configuration
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeValidPost', function () {
    return $this->toBeInstanceOf(Post::class)
        ->and($this->title)->not->toBeEmpty()
        ->and($this->content)->not->toBeEmpty()
        ->and($this->status)->toBeIn(['draft', 'published', 'archived']);
});

expect()->extend('toBeValidComment', function () {
    return $this->toBeInstanceOf(Comment::class)
        ->and($this->content)->not->toBeEmpty()
        ->and($this->status)->toBeIn(['pending', 'approved', 'rejected']);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createUserWithPosts(int $postCount = 3): User
{
    $user = User::factory()->create();
    Post::factory()->count($postCount)->create(['user_id' => $user->id]);

    return $user;
}

function createPostWithComments(int $commentCount = 5): Post
{
    $post = Post::factory()->create(['status' => 'published']);
    Comment::factory()->count($commentCount)->create([
        'post_id' => $post->id,
        'status' => 'approved',
    ]);

    return $post;
}

function mockService(string $serviceClass): MockInterface
{
    $mock = Mockery::mock($serviceClass);
    app()->instance($serviceClass, $mock);

    return $mock;
}

function assertDatabaseHasPost(array $attributes): void
{
    expect(Post::where($attributes)->exists())->toBeTrue();
}

function assertDatabaseHasComment(array $attributes): void
{
    expect(Comment::where($attributes)->exists())->toBeTrue();
}

/*
|--------------------------------------------------------------------------
| Test Hooks
|--------------------------------------------------------------------------
|
| Global test hooks that run before/after tests
|
*/
uses()->beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\Auth::logout();

    Event::fake();
    Queue::fake();
    Http::fake();
})->in('Feature', 'Unit');
