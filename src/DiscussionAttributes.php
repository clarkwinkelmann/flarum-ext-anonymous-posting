<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Discussion\Discussion;

class DiscussionAttributes
{
    public function __invoke(BasicDiscussionSerializer $serializer, Discussion $discussion): array
    {
        return [
            'isAnonymous' => !!$discussion->anonymous_user_id,
            // Both permission keys need to be included all the time for the frontend to correctly update state after switching
            'canDeAnonymize' => $serializer->getActor()->can('deAnonymize', $discussion),
            'canAnonymize' => $serializer->getActor()->can('anonymize', $discussion),
        ];
    }
}
