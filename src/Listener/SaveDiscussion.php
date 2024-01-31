<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\Event\DiscussionAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\DiscussionDeAnonymized;
use Flarum\Discussion\Event\Saving;
use Illuminate\Support\Arr;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Flarum\Discussion\Discussion;

class SaveDiscussion extends AbstractAnonymousStateEditor
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');
        $userId = null;
        if (class_exists(Tag::class) && !$event->post->exists && $attributes['isAnonymous']) {
            // Only modify user upon creation of Discussion or Post.
            if (isset($event->data['relationships']['tags']['data'])) {
                // Check for any tags available for imposter or avatar
                $userId = $this->anonymityRepository->anonymousUserIdByTags($event->data['relationships']['tags']['data'], "Discussion");
            }
        }
        if ($userId < 0) {
            // Avoid using Anonymous
            return;
        } else if ($userId > 0) {
            // Find user and replace actor
            $imposterActor = User::where('id', $userId)->first();
            if ($imposterActor) {
                $event->discussion->user_id = $userId;
                return;
            }
        }
        $this->apply($event->actor, $event->discussion, $attributes, DiscussionAnonymized::class, DiscussionDeAnonymized::class);

        // Anonymize deletion author if it's the author of an anonymous discussion
        if (
            $event->discussion->isDirty('hidden_user_id') &&
            $event->discussion->anonymous_user_id && 
            ($this->anonymityRepository->shouldAnonymizeEdit($event->discussion, $event->discussion->hidden_user_id) || $userId == 0)
        ) {
            // There are no relationships to unset here because Flarum doesn't have any for hiddenUser
            $event->discussion->hidden_user_id = null;
        }
    }
}
