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
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Extend;
use Flarum\Post\Event\Saving as PostSaving;
use Flarum\Post\Post;
use Flarum\User\User;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less'),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\Event())
        ->listen(DiscussionSaving::class, SaveDiscussion::class)
        ->listen(PostSaving::class, SavePost::class),

    (new Extend\Model(Discussion::class))
        ->belongsTo('anonymousUser', User::class, 'anonymous_user_id'),
    (new Extend\Model(Post::class))
        ->belongsTo('anonymousUser', User::class, 'anonymous_user_id'),

    (new Extend\ApiSerializer(BasicDiscussionSerializer::class))
        ->hasOne('anonymousUser', BasicUserSerializer::class)
        ->attributes(DiscussionAttributes::class),
    (new Extend\ApiSerializer(BasicPostSerializer::class))
        ->hasOne('anonymousUser', BasicUserSerializer::class)
        ->attributes(PostAttributes::class),
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
];
