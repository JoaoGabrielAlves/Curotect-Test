<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific private channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Post-specific channel (for post owners and viewers)
Broadcast::channel('post.{postId}', function ($user, $postId) {
    // Allow any authenticated user to listen to individual post updates
    return $user !== null;
});

// Comments channel (for authenticated users)
Broadcast::channel('comments', function ($user) {
    return $user !== null;
});
