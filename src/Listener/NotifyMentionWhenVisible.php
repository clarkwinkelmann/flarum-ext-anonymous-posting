<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use Flarum\Extension\ExtensionManager;
use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;

class NotifyMentionWhenVisible
{
    protected ExtensionManager $manager;
    protected NotificationSyncer $notifications;

    public function __construct(ExtensionManager $manager, NotificationSyncer $notifications)
    {
        $this->manager = $manager;
        $this->notifications = $notifications;
    }

    /**
     * @param Posted|Restored|Revised $event
     */
    public function handle($event)
    {
        if (!$this->manager->isEnabled('flarum-mentions')) {
            return;
        }

        $reply = $event->post;

        // Similar logic to Flarum Mention's UpdateMentionsMetadataWhenVisible to dispatch notifications to anonymous posts
        /**
         * @var Post[] $posts
         */
        $posts = $reply->mentionsPosts()
            ->with('anonymousUser')
            ->get()
            ->filter(function ($post) use ($reply) {
                return $post->anonymousUser &&
                    $post->anonymousUser->id !== $reply->user_id && // Also don't send notification if mentioning own anonymous post from non-anonymous post
                    $post->anonymousUser->id !== $reply->anonymous_user_id &&
                    $reply->isVisibleTo($post->anonymousUser);
            })
            ->all();

        foreach ($posts as $post) {
            $this->notifications->sync(new PostMentionedBlueprint($post, $reply), [$post->anonymousUser]);
        }
    }
}
