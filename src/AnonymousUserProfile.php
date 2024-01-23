<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Illuminate\Support\Arr;
use Flarum\User\User;

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

    public static function retrieveAll(array $anonymousUsers): array
    {
        $types = [
            "Discussion",
            "Post",
        ];
        $list = [
            "Discussion" => [],
            "Post" => []
        ];
        foreach ($anonymousUsers as $anonymousUser) {
            foreach ($types as $type) {
                if (Arr::get($anonymousUser, 'isCreating'.$type) && Arr::get($anonymousUser, 'isEnabled')) {
                    $userId = intval(Arr::get($anonymousUser, 'userId'));
                    $imposterActor = User::where('id', $userId)->firstOrFail();
                    if ($imposterActor) {
                        $list[$type][Arr::get($anonymousUser, 'tagName')] = [
                            "id" => $imposterActor->id,
                            "avatar_url" => $imposterActor->avatar_url,
                            "username" => $imposterActor->username,
                        ];
                    }
                }
            }
        }
        return $list;
    }
}