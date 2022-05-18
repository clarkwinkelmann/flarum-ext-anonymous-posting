<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Kilowhat\Formulaire\Submission;

class ForumAttributes
{
    protected SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke(ForumSerializer $serializer): array
    {
        $attributes = [];

        if ($serializer->getActor()->hasPermission('anonymous-posting.use')) {
            $attributes['canAnonymousPost'] = true;

            $avatarRules = json_decode($this->settings->get('anonymous-posting.formulaireAvatars'), true);

            if (is_array($avatarRules) && class_exists(Submission::class)) {
                $attributes['anonymousAvatarUrl'] = AnonymousAvatar::retrieve(
                    $avatarRules,
                    Submission::where('link_type', 'users')
                        ->where('link_id', $serializer->getActor()->id)
                        ->whereNull('hidden_at')
                        ->get()
                );
            }
        }

        return $attributes;
    }
}
