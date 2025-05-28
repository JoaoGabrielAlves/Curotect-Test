<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommentRepository;

beforeEach(function () {
    $this->repository = app(CommentRepository::class);
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create();
});

describe('create', function () {
    it('creates a new comment', function () {
        $data = [
            'content' => 'This is a test comment',
            'status' => 'approved',
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ];

        $result = $this->repository->create($data);

        expect($result)->toBeInstanceOf(Comment::class)
            ->and($result->content)->toBe($data['content'])
            ->and($result->status)->toBe($data['status'])
            ->and($result->post_id)->toBe($data['post_id'])
            ->and($result->user_id)->toBe($data['user_id']);

        $this->assertDatabaseHas('comments', $data);
    });
});

describe('update', function () {
    it('updates comment successfully', function () {
        $comment = Comment::factory()->create(['content' => 'Original content']);
        $updateData = ['content' => 'Updated content'];

        $result = $this->repository->update($comment, $updateData);

        expect($result)->toBeTrue()
            ->and($comment->fresh()->content)->toBe('Updated content');
    });
});

describe('delete', function () {
    it('deletes comment successfully', function () {
        $comment = Comment::factory()->create();

        $result = $this->repository->delete($comment);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });
});

describe('getByPost', function () {
    it('returns approved comments for post by default', function () {
        $approvedComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        $pendingComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
        ]);
        $otherPostComment = Comment::factory()->create([
            'post_id' => Post::factory()->create()->id,
            'status' => 'approved',
        ]);

        $result = $this->repository->getByPost($this->post);

        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($approvedComment->id);
    });

    it('filters comments by status when provided', function () {
        $approvedComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        $pendingComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
        ]);

        $result = $this->repository->getByPost($this->post, ['status' => 'pending']);

        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($pendingComment->id);
    });

    it('returns top-level comments with replies', function () {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);
        $replyComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
        ]);

        $result = $this->repository->getByPost($this->post);

        expect($result)->toHaveCount(1); // Only top-level comment
        expect($result->first()->id)->toBe($parentComment->id);
    });

    it('orders comments by creation date descending', function () {
        $oldComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'created_at' => now()->subDays(2),
        ]);
        $newComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getByPost($this->post);

        expect($result->first()->id)->toBe($newComment->id)
            ->and($result->last()->id)->toBe($oldComment->id);
    });
});

describe('getByUser', function () {
    it('returns comments by specific user', function () {
        $userComment = Comment::factory()->create(['user_id' => $this->user->id]);
        Comment::factory()->create(['user_id' => User::factory()->create()->id]);

        $result = $this->repository->getByUser($this->user);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($userComment->id);
    });

    it('filters user comments by status when provided', function () {
        $approvedComment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
        Comment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $result = $this->repository->getByUser($this->user, ['status' => 'approved']);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($approvedComment->id);
    });

    it('orders user comments by creation date descending', function () {
        $oldComment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);
        $newComment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getByUser($this->user);

        expect($result->first()->id)->toBe($newComment->id);
        expect($result->last()->id)->toBe($oldComment->id);
    });
});

describe('getPendingModeration', function () {
    it('returns comments with pending status', function () {
        $pendingComment = Comment::factory()->create(['status' => 'pending']);
        Comment::factory()->create(['status' => 'approved']);
        Comment::factory()->create(['status' => 'rejected']);

        $result = $this->repository->getPendingModeration();

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($pendingComment->id);
    });

    it('orders pending comments by creation date ascending', function () {
        $oldPendingComment = Comment::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);
        $newPendingComment = Comment::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getPendingModeration();

        expect($result->first()->id)->toBe($oldPendingComment->id)
            ->and($result->last()->id)->toBe($newPendingComment->id);
    });
});

describe('findWithRelations', function () {
    it('finds comment with default relations', function () {
        $comment = Comment::factory()->create();

        $result = $this->repository->findWithRelations($comment->id);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($comment->id)
            ->and($result->relationLoaded('user'))->toBeTrue()
            ->and($result->relationLoaded('post'))->toBeTrue();
    });

    it('returns null for non-existent comment', function () {
        $result = $this->repository->findWithRelations(999);

        expect($result)->toBeNull();
    });

    it('loads specified relations', function () {
        $comment = Comment::factory()->create();

        $result = $this->repository->findWithRelations($comment->id, ['user', 'replies']);

        expect($result)->not->toBeNull()
            ->and($result->relationLoaded('user'))->toBeTrue()
            ->and($result->relationLoaded('replies'))->toBeTrue();
    });
});

describe('find', function () {
    it('finds comment by id', function () {
        $comment = Comment::factory()->create(['content' => 'Test comment content']);

        $result = $this->repository->find($comment->id);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($comment->id)
            ->and($result->content)->toBe('Test comment content');
    });

    it('returns null for non-existent comment', function () {
        $result = $this->repository->find(999);

        expect($result)->toBeNull();
    });
});

describe('getByPostPaginated', function () {
    it('returns paginated comments for post with default status filter', function () {
        Comment::factory()->count(15)->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        Comment::factory()->count(5)->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
        ]);

        $result = $this->repository->getByPostPaginated($this->post, [], 10);

        expect($result->items())->toHaveCount(10)
            ->and($result->total())->toBe(15)
            ->and($result->currentPage())->toBe(1);
    });

    it('filters paginated comments by status', function () {
        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        Comment::factory()->count(2)->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
        ]);

        $result = $this->repository->getByPostPaginated($this->post, ['status' => 'pending']);

        expect($result->items())->toHaveCount(2);
    });

    it('filters paginated comments by parent_id', function () {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);
        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
        ]);
        Comment::factory()->count(2)->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        $result = $this->repository->getByPostPaginated($this->post, ['parent_id' => $parentComment->id]);

        expect($result->items())->toHaveCount(3);
    });

    it('orders paginated comments by creation date descending', function () {
        $oldComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'created_at' => now()->subDays(2),
        ]);
        $newComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getByPostPaginated($this->post);

        expect($result->first()->id)->toBe($newComment->id)
            ->and($result->last()->id)->toBe($oldComment->id);
    });
});

describe('getTopLevelByPost', function () {
    it('returns only top-level approved comments', function () {
        $topLevelComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $topLevelComment->id,
        ]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
            'parent_id' => null,
        ]);

        $result = $this->repository->getTopLevelByPost($this->post);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($topLevelComment->id);
    });

    it('loads user and replies relationships', function () {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        $result = $this->repository->getTopLevelByPost($this->post);

        expect($result->first()->relationLoaded('user'))->toBeTrue()
            ->and($result->first()->relationLoaded('replies'))->toBeTrue();
    });

    it('orders top-level comments by creation date descending', function () {
        $oldComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
            'created_at' => now()->subDays(2),
        ]);
        $newComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getTopLevelByPost($this->post);

        expect($result->first()->id)->toBe($newComment->id)
            ->and($result->last()->id)->toBe($oldComment->id);
    });
});

describe('getReplies', function () {
    it('returns approved replies for comment', function () {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
        ]);
        $approvedReply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
        ]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'pending',
            'parent_id' => $parentComment->id,
        ]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => null,
        ]);

        $result = $this->repository->getReplies($parentComment);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($approvedReply->id);
    });

    it('orders replies by creation date ascending', function () {
        $parentComment = Comment::factory()->create(['post_id' => $this->post->id]);
        $oldReply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
            'created_at' => now()->subDays(2),
        ]);
        $newReply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
            'created_at' => now()->subDay(),
        ]);

        $result = $this->repository->getReplies($parentComment);

        expect($result->first()->id)->toBe($oldReply->id)
            ->and($result->last()->id)->toBe($newReply->id);
    });

    it('loads user relationship for replies', function () {
        $parentComment = Comment::factory()->create(['post_id' => $this->post->id]);
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => 'approved',
            'parent_id' => $parentComment->id,
        ]);

        $result = $this->repository->getReplies($parentComment);

        expect($result->first()->relationLoaded('user'))->toBeTrue();
    });
});
