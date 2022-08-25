<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Controller\CreateDiscussionController;
use Flarum\Api\Controller\CreatePostController;
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Controller\ListPostsController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowPostController;
use Flarum\Api\Controller\UpdateDiscussionController;
use Flarum\Api\Controller\UpdatePostController;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Extend;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Event\Saving as PostSaving;
use Flarum\Post\Post;
use Flarum\Settings\Event\Saving as EventSaving;
use Flarum\User\User;
use Kilowhat\Formulaire\Submission;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less'),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\Event())
        ->listen(Posted::class, Listener\NotifyMentionWhenVisible::class)
        ->listen(Restored::class, Listener\NotifyMentionWhenVisible::class)
        ->listen(Revised::class, Listener\NotifyMentionWhenVisible::class)
        ->listen(DiscussionSaving::class, Listener\SaveDiscussion::class)
        ->listen(PostSaving::class, Listener\SavePost::class)
        ->listen(EventSaving::class, Listener\SaveSettings::class),

    ...array_map(function (string $className) {
        return (new Extend\Model($className))
            ->belongsTo('anonymousUser', User::class, 'anonymous_user_id')
            // This relationship doesn't exist in Formulaire extension because it doesn't make much sense to retrieve public data this way
            // (because it needs to check which forms are visible to the current user first)
            // But for our use case here we don't care about form visibility or open/close status, so we can just get all of them
            // Setting it on the post/discussion also saves up one database request by skipping the anonymousUser relationship
            ->relationship('anonymousUserSubmissions', function (AbstractModel $model) {
                return $model->hasMany(Submission::class, 'link_id', 'anonymous_user_id')
                    ->where('link_type', 'users')
                    ->whereNull('hidden_at');
            });
    }, [Discussion::class, Post::class]),

    (new Extend\Settings())
        ->default('anonymous-posting.composerHelpTextPosition', 'visible'),

    (new Extend\ApiSerializer(BasicDiscussionSerializer::class))
        ->hasOne('anonymousUser', BasicUserSerializer::class)
        ->attributes(DiscussionAndPostAttributes::class),
    (new Extend\ApiSerializer(BasicPostSerializer::class))
        ->hasOne('anonymousUser', BasicUserSerializer::class)
        ->attributes(DiscussionAndPostAttributes::class),
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(ForumAttributes::class),

    (new Extend\ApiController(ListDiscussionsController::class))
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),
    (new Extend\ApiController(ShowDiscussionController::class))
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation())
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation('posts.')),
    (new Extend\ApiController(CreateDiscussionController::class))
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),
    (new Extend\ApiController(UpdateDiscussionController::class))
        ->addInclude('user') // Needed for correct refresh when de-anonymizing
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),

    (new Extend\ApiController(ListPostsController::class))
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),
    (new Extend\ApiController(ShowPostController::class))
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),
    (new Extend\ApiController(CreatePostController::class))
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),
    (new Extend\ApiController(UpdatePostController::class))
        ->addInclude('user') // Needed for correct refresh when de-anonymizing
        ->prepareDataForSerialization(new IncludeAnonymousUserRelation()),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, Policy\DiscussionPolicy::class)
        ->modelPolicy(Post::class, Policy\PostPolicy::class),

    (new Extend\ServiceProvider())
        ->register(FakeUserRelation\FakeUserRelationServiceProvider::class)
        ->register(Provider\FilterServiceProvider::class),
];
