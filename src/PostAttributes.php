<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Post\Post;

class PostAttributes
{
    public function __invoke(BasicPostSerializer $serializer, Post $post): array
    {
        return [
            'isAnonymous' => !!$post->anonymous_user_id,
            // Both permission keys need to be included all the time for the frontend to correctly update state after switching
            'canDeAnonymize' => $serializer->getActor()->can('deAnonymize', $post),
            'canAnonymize' => $serializer->getActor()->can('anonymize', $post),
        ];
    }
}
