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
        if (class_exists(Tag::class) && isset($event->data['relationships']['tags']['data'])) {
            // Check for any tags available for imposter or avatar
            if(count($event->data['relationships']['tags']['data']) > 0) {
                $tagId = $event->data['relationships']['tags']['data'][0]["id"];
                $tag = Tag::where('id', $tagId)->firstOrFail();
                if ($tag) {
                    $userId = $this->anonymityRepository->anonymousUserIdByTagName($tag->name, "Discussion");
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
                $event->discussion->user_id = $userId;
                return $event;
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
