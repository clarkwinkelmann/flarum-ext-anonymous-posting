<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\ForumSerializer;

class ForumAttributes
{
    public function __invoke(ForumSerializer $serializer): array
    {
        if ($serializer->getActor()->hasPermission('anonymous-posting.use')) {
            return [
                'canAnonymousPost' => true,
            ];
        }

        return [];
    }
}
