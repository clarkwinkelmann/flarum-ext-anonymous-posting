<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\Event\PostAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\PostDeAnonymized;
use Flarum\Post\Event\Saving;
use Illuminate\Support\Arr;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Flarum\Discussion\Discussion;

class SavePost extends AbstractAnonymousStateEditor
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');
        $userId = null;
        if (class_exists(Tag::class) && !$event->post->exists && $attributes['isAnonymous']) {
            // Only modify user upon creation of Discussion or Post.
            if ($event->post->discussion->first_post_id == NULL && isset($event->data['relationships']['tags']['data'])) {
                // Identify that the post is linked at the creation of the discussion
                $userId = $this->anonymityRepository->anonymousUserIdByTags($event->data['relationships']['tags']['data'], "Discussion");
            } else if (isset($event->post->discussion->tags)) {
                $userId = $this->anonymityRepository->anonymousUserIdByTags($event->post->discussion->tags, "Post");
            }
        }
        if ($userId < 0) {
            // Avoid using Anonymous
            return;
        } else if ($userId > 0) {
            // Find user and replace actor
            $imposterActor = User::where('id', $userId)->first();
            if ($imposterActor) {
                $event->post->user_id = $userId;
                return;
            }
        }
        $this->apply($event->actor, $event->post, $attributes, PostAnonymized::class, PostDeAnonymized::class);

        // Anonymize deletion/edit author if it's the author of an anonymous post
        if (
            $event->post->isDirty('edited_user_id') &&
            $event->post->anonymous_user_id &&
            ($this->anonymityRepository->shouldAnonymizeEdit($event->post, $event->post->edited_user_id) || $userId == 0)
        ) {
            $event->post->editedUser()->dissociate();
        }
        if (
            $event->post->isDirty('hidden_user_id') &&
            $event->post->anonymous_user_id &&
            ($this->anonymityRepository->shouldAnonymizeEdit($event->post, $event->post->hidden_user_id) || $userId == 0)
        ) {
            $event->post->hiddenUser()->dissociate();
        }
    }
}
