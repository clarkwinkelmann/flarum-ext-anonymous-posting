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
        if (Arr::get($event->data, 'type') == 'discussions' && class_exists(Tag::class) && isset($event->data['relationships']['tags']['data'])) {
            // Identify that the post is linked at the creation of the discussion
            if(count($event->data['relationships']['tags']['data']) > 0) {
                $tagId = $event->data['relationships']['tags']['data'][0]["id"];
                $tag = Tag::where('id', $tagId)->firstOrFail();
                if ($tag) {
                    $userId = $this->anonymityRepository->anonymousUserIdByTagName($tag->name, "Discussion");
                }
            }
        } else if (Arr::get($event->data, 'type') == 'posts' && class_exists(Tag::class) && isset($event->post->discussion->tags)) {
            if(count($event->post->discussion->tags) > 0) {
                $tag = $event->post->discussion->tags[0];
                if ($tag) {
                    $userId = $this->anonymityRepository->anonymousUserIdByTagName($tag->name, "Post");
                }
            }
        }
        if ($userId === null) {
            // Get default anonymous user profile
            $userId = $this->anonymityRepository->anonymousUserIdDefault();
        }
        if ($userId > 0) {
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
