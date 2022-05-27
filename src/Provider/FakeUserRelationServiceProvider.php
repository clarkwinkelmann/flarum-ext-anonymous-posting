<?php

namespace ClarkWinkelmann\AnonymousPosting\Provider;

use ClarkWinkelmann\AnonymousPosting\AnonymousUser;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\CommentPost;

class FakeUserRelationServiceProvider extends AbstractServiceProvider
{
    public function boot()
    {
        // Workaround subject/blade notifications trying to access $post->user->display_name of anonymous posts
        // Doing this in the NotifyMentionWhenVisible listener would only fix post mentions to anonymous posts,
        // but not post mentions to regular posts or user mentions since those have already been handled at that point
        // By doing the change in Post::saved, we ensure it happens late enough to not interfere with save but before events are dispatched
        CommentPost::saved(function ($post) {
            if ($post->anonymous_user_id) {
                $post->setRelation('user', new AnonymousUser());
            }
        });
    }
}
