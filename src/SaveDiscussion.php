<?php

namespace ClarkWinkelmann\AnonymousPosting;

use ClarkWinkelmann\AnonymousPosting\Event\DiscussionAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\DiscussionDeAnonymized;
use Flarum\Discussion\Event\Saving;
use Illuminate\Support\Arr;

class SaveDiscussion
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');

        if (Arr::exists($attributes, 'isAnonymous')) {
            if (Arr::get($attributes, 'isAnonymous')) {
                if ($event->discussion->anonymous_user_id) {
                    return;
                }

                if ($event->discussion->exists) {
                    $event->actor->assertCan('anonymize', $event->discussion);

                    $event->discussion->anonymous_user_id = $event->discussion->user_id;
                } else {
                    $event->actor->assertCan('anonymous-posting.use');

                    $event->discussion->anonymous_user_id = $event->actor->id;
                }

                $event->discussion->user_id = null;

                $event->discussion->raise(new DiscussionAnonymized($event->discussion, $event->actor));
            } else if ($event->discussion->exists) {
                if (!$event->discussion->anonymous_user_id) {
                    return;
                }

                $event->actor->assertCan('deAnonymize', $event->discussion);

                $event->discussion->user_id = $event->discussion->anonymous_user_id;
                $event->discussion->anonymous_user_id = null;

                $event->discussion->raise(new DiscussionDeAnonymized($event->discussion, $event->actor));
            }
        }

        // Anonymize deletion author if it's the author of an anonymous discussion
        if (
            $event->discussion->isDirty('hidden_user_id') &&
            $event->discussion->anonymous_user_id &&
            $event->discussion->anonymous_user_id === $event->discussion->hidden_user_id) {
            $event->discussion->hidden_user_id = null;
        }
    }
}
