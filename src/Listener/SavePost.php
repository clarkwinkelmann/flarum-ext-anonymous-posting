<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\Event\PostAnonymized;
use ClarkWinkelmann\AnonymousPosting\Event\PostDeAnonymized;
use Flarum\Post\Event\Saving;
use Illuminate\Support\Arr;
use Flarum\Tags\Tag;
use Flarum\User\User;

class SavePost extends AbstractAnonymousStateEditor
{
    public function handle(Saving $event)
    {
        $attributes = (array)Arr::get($event->data, 'attributes');

        $imposterActor = null;
        if (class_exists(Tag::class) && isset($event->data['relationships']['tags']['data'])) {
            $tagId = $event->data['relationships']['tags']['data'][0]["id"];
            $tag = Tag::where('id', $tagId)->firstOrFail();
            if ($tag) {
                $userId = $this->anonymityRepository->anonymousUserIdByTagName($tag->name);
            }
        }
        if ($userId == null) {
            // Get default anonymous user profile
            $userId = $this->anonymityRepository->anonymousUserIdDefault();
        }
        if ($userId != null) {
            // Find user and replace actor
            $imposterActor = User::where('id', $userId)->firstOrFail();
            if ($imposterActor) {
                $event->post->user_id = $userId;
                return $event;
            }
        }
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
