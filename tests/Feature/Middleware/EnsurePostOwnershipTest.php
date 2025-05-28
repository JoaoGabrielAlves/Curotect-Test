<?php

use App\Http\Middleware\EnsurePostOwnership;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->middleware = new EnsurePostOwnership;
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->post = Post::factory()->create(['user_id' => $this->user->id]);
});

it('allows access when user owns the post', function () {
    $request = Request::create('/posts/'.$this->post->id);
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/posts/{post}', []);
        $route->bind(Request::create('/posts/'.$this->post->id));
        $route->setParameter('post', $this->post);

        return $route;
    });

    $this->actingAs($this->user);

    $response = $this->middleware->handle($request, function () {
        return response('success');
    });

    expect($response->getContent())->toBe('success');
});

it('denies access when user does not own the post', function () {
    $request = Request::create('/posts/'.$this->post->id, 'GET');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/posts/{post}', []);
        $route->bind(Request::create('/posts/'.$this->post->id));
        $route->setParameter('post', $this->post);

        return $route;
    });

    $this->actingAs($this->otherUser);

    $this->middleware->handle($request, function () {
        return response('success');
    });
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'You do not have permission to access this post.');

it('denies access when user is not authenticated', function () {
    $request = Request::create('/posts/'.$this->post->id, 'GET');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/posts/{post}', []);
        $route->bind(Request::create('/posts/'.$this->post->id));
        $route->setParameter('post', $this->post);

        return $route;
    });

    $this->middleware->handle($request, function () {
        return response('success');
    });
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'You do not have permission to access this post.');

it('returns 404 when post parameter is not a Post instance', function () {
    $request = Request::create('/posts/invalid', 'GET');
    $request->setRouteResolver(function () {
        $route = new \Illuminate\Routing\Route('GET', '/posts/{post}', []);
        $route->bind(Request::create('/posts/invalid'));
        $route->setParameter('post', 'invalid');

        return $route;
    });

    $this->middleware->handle($request, function () {
        return response('success');
    });
})->throws(NotFoundHttpException::class);
