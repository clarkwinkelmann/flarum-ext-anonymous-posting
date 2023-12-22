<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Illuminate\Support\Arr;

class AnonymousUserProfile
{
    public static function retrieve(array $anonymousUsers, string $tagName, string $type): ?int
    {
        foreach ($anonymousUsers as $anonymousUser) {
            if ($tagName == Arr::get($anonymousUser, 'tagName') && Arr::get($anonymousUser, 'isCreating'.$type) && Arr::get($anonymousUser, 'isEnabled')) {
                $userId = intval(Arr::get($anonymousUser, 'userId'));
                return $userId > 0? $userId : 0;
            }
        }

        return null;
    }
}
