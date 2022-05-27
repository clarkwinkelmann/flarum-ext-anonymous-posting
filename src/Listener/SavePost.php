<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\Event\PostAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\PostDeAnonymized;
use Flarum\Post\Event\Saving;
use Illuminate\Support\Arr;

class SavePost
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');

        if (Arr::exists($attributes, 'isAnonymous')) {
            if (Arr::get($attributes, 'isAnonymous')) {
                if ($event->post->anonymous_user_id) {
                    return;
                }

                if ($event->post->exists) {
                    $event->actor->assertCan('anonymize', $event->post);

                    $event->post->anonymousUser()->associate($event->post->user_id);
                } else {
                    $event->actor->assertCan('anonymous-posting.use');

                    $event->post->anonymousUser()->associate($event->actor);
                }

                $event->post->user()->dissociate();

                $event->post->raise(new PostAnonymized($event->post, $event->actor));
            } else if ($event->post->exists) {
                if (!$event->post->anonymous_user_id) {
                    return;
                }

                $event->actor->assertCan('deAnonymize', $event->post);

                $event->post->user()->associate($event->post->anonymous_user_id);
                $event->post->anonymousUser()->dissociate();

                $event->post->raise(new PostDeAnonymized($event->post, $event->actor));
            }
        }

        // Anonymize deletion/edit author if it's the author of an anonymous post
        if (
            $event->post->isDirty('edited_user_id') &&
            $event->post->anonymous_user_id &&
            $event->post->anonymous_user_id === $event->post->edited_user_id) {
            $event->post->editedUser()->dissociate();
        }
        if (
            $event->post->isDirty('hidden_user_id') &&
            $event->post->anonymous_user_id &&
            $event->post->anonymous_user_id === $event->post->hidden_user_id) {
            $event->post->hiddenUser()->dissociate();
        }
    }
}
