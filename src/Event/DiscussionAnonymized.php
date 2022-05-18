<?php

namespace ClarkWinkelmann\AnonymousPosting\Event;

use Flarum\Discussion\Discussion;
use Flarum\User\User;

class DiscussionAnonymized
{
    public Discussion $discussion;
    public User $actor;

    public function __construct(Discussion $discussion, User $actor)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
    }
}
