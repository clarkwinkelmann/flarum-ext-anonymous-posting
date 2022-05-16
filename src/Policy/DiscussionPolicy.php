<?php

namespace ClarkWinkelmann\AnonymousPosting\Policy;

use Flarum\Discussion\Discussion;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class DiscussionPolicy extends AbstractPolicy
{
    public function anonymize(User $actor, Discussion $discussion)
    {
        return $discussion->user_id && !$discussion->anonymous_user_id && $actor->hasPermission('anonymous-posting.moderate');
    }

    public function deAnonymize(User $actor, Discussion $discussion)
    {
        return $discussion->anonymous_user_id && !$discussion->user_id && $actor->hasPermission('anonymous-posting.moderate');
    }
}
