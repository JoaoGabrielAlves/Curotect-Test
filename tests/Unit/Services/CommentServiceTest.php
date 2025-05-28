<?php

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->commentService = app(CommentService::class);
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->post = Post::factory()->create(['user_id' => $this->otherUser->id]);
});

describe('createComment', function () {
    it('creates comment with business logic applied', function () {
        $data = [
            'content' => 'This is a test comment',
            'parent_id' => null,
        ];

        $result = $this->commentService->createComment($data, $this->post, $this->user);

        expect($result)->toBeInstanceOf(Comment::class)
            ->and($result->content)->toBe(trim($data['content']))
            ->and($result->post_id)->toBe($this->post->id)
            ->and($result->user_id)->toBe($this->user->id)
            ->and($result->status)->toBe('approved');
        // Default status

        $this->assertDatabaseHas('comments', [
            'content' => trim($data['content']),
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);

        Event::assertDispatched(CommentCreated::class, function ($event) use ($result) {
            return $event->comment->id === $result->id &&
                   $event->comment->user->id === $this->user->id &&
                   $event->comment->post->id === $this->post->id;
        });
    });

    it('auto-approves comments from post owner', function () {
        $data = ['content' => 'Post owner comment'];
        $postOwnerPost = Post::factory()->create(['user_id' => $this->user->id]);

        $result = $this->commentService->createComment($data, $postOwnerPost, $this->user);

        expect($result->status)->toBe('approved');
        $this->assertDatabaseHas('comments', [
            'content' => trim($data['content']),
            'status' => 'approved',
        ]);

        Event::assertDispatched(CommentCreated::class, function ($event) use ($result) {
            return $event->comment->id === $result->id;
        });
    });

    it('creates reply to parent comment', function () {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
        ]);

        $data = [
            'content' => 'This is a reply',
            'parent_id' => $parentComment->id,
        ];

        $result = $this->commentService->createComment($data, $this->post, $this->user);

        expect($result->parent_id)->toBe($parentComment->id);
        $this->assertDatabaseHas('comments', [
            'content' => trim($data['content']),
            'parent_id' => $parentComment->id,
        ]);

        Event::assertDispatched(CommentCreated::class, function ($event) use ($result, $parentComment) {
            return $event->comment->id === $result->id &&
                   $event->comment->parent->id === $parentComment->id;
        });
    });
});

describe('updateComment', function () {
    it('updates comment with business logic applied', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        $data = ['content' => 'Updated comment content'];

        $result = $this->commentService->updateComment($comment, $data);

        expect($result->content)->toBe(trim($data['content']));
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => trim($data['content']),
        ]);
    });

    it('updates comment status when provided', function () {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $data = [
            'content' => 'Updated content',
            'status' => 'approved',
        ];

        $this->commentService->updateComment($comment, $data);

        expect($comment->fresh()->status)->toBe('approved');
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'approved',
        ]);
    });
});

describe('deleteComment', function () {
    it('deletes comment successfully', function () {
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);

        $result = $this->commentService->deleteComment($comment);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });
});

describe('getPostComments', function () {
    it('returns comments for post with filters', function () {
        $approvedComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
        ]);

        $result = $this->commentService->getPostComments($this->post, ['status' => 'approved']);

        expect($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($approvedComment->id);
    });

    it('returns all approved comments by default', function () {
        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
        ]);

        $result = $this->commentService->getPostComments($this->post, []);

        expect($result->count())->toBe(3); // Only approved comments
    });
});

describe('getUserComments', function () {
    it('returns user comments with privacy controls for different viewer', function () {
        Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $result = $this->commentService->getUserComments($this->user, $this->otherUser, []);

        expect($result->count())->toBe(1); // Only approved comment visible
    });

    it('returns user comments without privacy controls for same user', function () {
        Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $result = $this->commentService->getUserComments($this->user, $this->user, []);

        expect($result->count())->toBe(2); // Both comments visible to owner
    });
});

describe('moderateComment', function () {
    it('approves comment successfully', function () {
        Auth::shouldReceive('id')->andReturn($this->user->id);

        $comment = Comment::factory()->create(['status' => 'pending']);

        $result = $this->commentService->moderateComment($comment, 'approve');

        expect($result)->toBeTrue()
            ->and($comment->fresh()->status)->toBe('approved');
    });

    it('returns false for invalid action', function () {

        $comment = Comment::factory()->create(['status' => 'pending']);

        $result = $this->commentService->moderateComment($comment, 'invalid_action');

        expect($result)->toBeFalse();
    });
});

describe('getCommentsForModeration', function () {
    it('returns comments pending moderation', function () {
        $pendingComment = Comment::factory()->create(['status' => 'pending']);
        Comment::factory()->create(['status' => 'approved']);

        $result = $this->commentService->getCommentsForModeration();

        expect($result->count())->toBe(1)
            ->and($result->first()->id)->toBe($pendingComment->id);
    });
});
