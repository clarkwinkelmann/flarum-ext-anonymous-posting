<?php

namespace ClarkWinkelmann\AnonymousPosting\Policy;

use Carbon\Carbon;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class PostPolicy extends AbstractPolicy
{
    protected SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function anonymize(User $actor, Post $post)
    {
        return $post->user_id && !$post->anonymous_user_id && $actor->hasPermission('anonymous-posting.moderate');
    }

    public function deAnonymize(User $actor, Post $post)
    {
        return $post->anonymous_user_id && !$post->user_id && $actor->hasPermission('anonymous-posting.moderate');
    }

    public function edit(User $actor, Post $post)
    {
        // Same code as original but check anonymous user ID instead of regular user ID
        if ($post->anonymous_user_id == $actor->id && (!$post->hidden_at || $post->hidden_user_id == $actor->id) && $actor->can('reply', $post->discussion)) {
            $allowEditing = $this->settings->get('allow_post_editing');

            if ($allowEditing === '-1'
                || ($allowEditing === 'reply' && $post->number >= $post->discussion->last_post_number)
                || (is_numeric($allowEditing) && $post->created_at->diffInMinutes(new Carbon) < $allowEditing)) {
                return $this->allow();
            }
        }
    }

    public function hide(User $actor, Post $post)
    {
        // Same code as original but needs to be called again due to edit() having been changed
        return $this->edit($actor, $post);
    }
}
