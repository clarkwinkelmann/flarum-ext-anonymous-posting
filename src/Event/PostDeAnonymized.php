<?php

namespace ClarkWinkelmann\AnonymousPosting\Event;

use Flarum\Post\Post;
use Flarum\User\User;

class PostDeAnonymized
{
    public Post $post;
    public User $actor;

    public function __construct(Post $post, User $actor)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
