<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Illuminate\Support\Arr;

class AnonymousUserProfile
{
    public static function retrieve(array $anonymousUsers, string $tagName): ?int
    {
        foreach ($anonymousUsers as $anonymousUser) {
            if ($tagName == Arr::get($anonymousUser, 'tagName')) {
                $userId = intval(Arr::get($anonymousUser, 'userId'));
                return $userId > 0? $userId : null;
            }
        }

        return null;
    }
}
