<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Illuminate\Support\Arr;
use Flarum\User\User;
use Flarum\Tags\Tag;

class AnonymousUserProfile
{
    public static function retrieve(array $anonymousUsers, string $tagName, string $type): ?int
    {
        foreach ($anonymousUsers as $anonymousUser) {
            if ($tagName == Arr::get($anonymousUser, 'tagName') && Arr::get($anonymousUser, 'isCreating'.$type) && Arr::get($anonymousUser, 'isEnabled')) {
                $userId = intval(Arr::get($anonymousUser, 'userId'));
                return $userId;
            }
        }

        return null;
    }

    public static function retrieveAll(array $anonymousUsers, User $actor): array
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
                    if ($userId != 0) {
                        $tagName = Arr::get($anonymousUser, 'tagName');
                        $tag = Tag::where('name', $tagName)->first();
                        if ($tag) {
                            if ($userId < 0) {
                                $list[$type][$tag->id] = [
                                    "name" => $tagName,
                                    "user_id" => $actor->id,
                                    "user_avatar_url" => $actor->avatar_url,
                                    "user_username" => $actor->username,
                                ];
                            } else {
                                // Assign current user to tag
                                $imposterActor = User::where('id', $userId)->first();
                                if ($imposterActor) {
                                    $list[$type][$tag->id] = [
                                        "name" => $tagName,
                                        "user_id" => $imposterActor->id,
                                        "user_avatar_url" => $imposterActor->avatar_url,
                                        "user_username" => $imposterActor->username,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }
}