<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Database\AbstractModel;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Flarum\Tags\Tag;
use Flarum\Database\Eloquent\Collection;

class AnonymityRepository
{
    protected SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function defaultValue(User $user): bool
    {
        if ($user->hasPermission('anonymous-posting.use')) {
            return (bool)$this->settings->get('anonymous-posting.defaultAnonymityWhenAbleToSwitch');
        }

        return (bool)$this->settings->get('anonymous-posting.defaultAnonymity');
    }

    public function shouldAnonymizeEdit(AbstractModel $model, $editorUserId): bool
    {
        if ($this->settings->get('anonymous-posting.alwaysAnonymiseEdits')) {
            return true;
        }

        return $model->anonymous_user_id === $editorUserId;
    }

    public function anonymousUserIdByTags(array | Collection $tags, string $type): ?int
    {
        $userId = null;
        foreach ($tags as &$tag) {
            if (isset($tag->id)) {
                $userId = $this->anonymousUserIdByTagName($tag->name, $type);
            } else {
                $tagFound = Tag::where('id', $tag['id'])->first();
                if ($tagFound) {
                    $userId = $this->anonymousUserIdByTagName($tagFound->name, $type);
                }
            }
            if($userId != null) {
                break;
            }
        }
        if ($userId === null) {
            // Get default anonymous user profile
            $userId = $this->anonymousUserIdDefault();
        }
        return $userId;
    }

    public function anonymousUserIdByTagName(string $tagName, string $type): ?int
    {
        $anonymousUsers = json_decode($this->settings->get('anonymous-posting.anonymousUsers'), true);
        return AnonymousUserProfile::retrieve($anonymousUsers, $tagName, $type);
    }

    public function anonymousUserIdDefault(): ?int
    {
        if (intval($this->settings->get('anonymous-posting.defaultAnonymousUserProfile')) > 0) {
            return intval($this->settings->get('anonymous-posting.defaultAnonymousUserProfile'));
        }

        return null;
    }

    public function listOfAnonymousUsersByTag(array | Collection $tags, User $actor): array
    {
        $anonymousUsers = json_decode($this->settings->get('anonymous-posting.anonymousUsers'), true);
        return AnonymousUserProfile::retrieveAll($anonymousUsers, $actor);
    }
}