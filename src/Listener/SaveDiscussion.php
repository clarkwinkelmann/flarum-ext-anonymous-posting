<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\Event\DiscussionAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\DiscussionDeAnonymized;
use Flarum\Discussion\Event\Saving;
use Illuminate\Support\Arr;

class SaveDiscussion extends AbstractAnonymousStateEditor
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');

        $this->apply($event->actor, $event->discussion, $attributes, DiscussionAnonymized::class, DiscussionDeAnonymized::class);

        // Anonymize deletion author if it's the author of an anonymous discussion
        if (
            $event->discussion->isDirty('hidden_user_id') &&
            $event->discussion->anonymous_user_id &&
            $this->anonymityRepository->shouldAnonymizeEdit($event->discussion, $event->discussion->hidden_user_id)) {
            // There are no relationships to unset here because Flarum doesn't have any for hiddenUser
            $event->discussion->hidden_user_id = null;
        }
    }
}
