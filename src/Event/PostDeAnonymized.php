<?php

namespace ClarkWinkelmann\AnonymousPosting\Event;

use Flarum\Post\Post;
use Flarum\User\User;

class PostDeAnonymized
{
    public $post;
    public $actor;

    public function __construct(Post $post, User $actor)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
