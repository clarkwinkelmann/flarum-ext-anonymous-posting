<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\Event\PostAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\PostDeAnonymized;
use Flarum\Post\Event\Saving;
use Illuminate\Support\Arr;

class SavePost extends AbstractAnonymousStateEditor
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');

        $this->apply($event->actor, $event->post, $attributes, PostAnonymized::class, PostDeAnonymized::class);

        // Anonymize deletion/edit author if it's the author of an anonymous post
        if (
            $event->post->isDirty('edited_user_id') &&
            $event->post->anonymous_user_id &&
            $this->anonymityRepository->shouldAnonymizeEdit($event->post, $event->post->edited_user_id)) {
            $event->post->editedUser()->dissociate();
        }
        if (
            $event->post->isDirty('hidden_user_id') &&
            $event->post->anonymous_user_id &&
            $this->anonymityRepository->shouldAnonymizeEdit($event->post, $event->post->hidden_user_id)) {
            $event->post->hiddenUser()->dissociate();
        }
    }
}
