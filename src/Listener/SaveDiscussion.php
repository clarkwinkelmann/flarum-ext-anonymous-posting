<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

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

                    $event->discussion->anonymousUser()->associate($event->discussion->user_id);
                } else {
                    $event->actor->assertCan('anonymous-posting.use');

                    $event->discussion->anonymousUser()->associate($event->actor);
                }

                // Use dissociate instead of setting user_id to null, that way any loaded relationship is unset,
                // otherwise it might end up in the serializer output if it was read from another event listener
                $event->discussion->user()->dissociate();

                $event->discussion->raise(new DiscussionAnonymized($event->discussion, $event->actor));
            } else if ($event->discussion->exists) {
                if (!$event->discussion->anonymous_user_id) {
                    return;
                }

                $event->actor->assertCan('deAnonymize', $event->discussion);

                $event->discussion->user()->associate($event->discussion->anonymous_user_id);
                $event->discussion->anonymousUser()->dissociate();

                $event->discussion->raise(new DiscussionDeAnonymized($event->discussion, $event->actor));
            }
        }

        // Anonymize deletion author if it's the author of an anonymous discussion
        if (
            $event->discussion->isDirty('hidden_user_id') &&
            $event->discussion->anonymous_user_id &&
            $event->discussion->anonymous_user_id === $event->discussion->hidden_user_id) {
            // There are no relationships to unset here because Flarum doesn't have any for hiddenUser
            $event->discussion->hidden_user_id = null;
        }
    }
}
