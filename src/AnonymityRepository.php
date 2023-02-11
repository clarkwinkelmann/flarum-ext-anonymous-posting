<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Database\AbstractModel;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;

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
}
