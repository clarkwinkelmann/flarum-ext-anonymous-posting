<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Settings\SettingsRepositoryInterface;
use Kilowhat\Formulaire\Submission;

class ForumAttributes
{
    protected SettingsRepositoryInterface $settings;
    protected AnonymityRepository $anonymityRepository;

    public function __construct(SettingsRepositoryInterface $settings, AnonymityRepository $anonymityRepository)
    {
        $this->settings = $settings;
        $this->anonymityRepository = $anonymityRepository;
    }

    public function __invoke(ForumSerializer $serializer): array
    {
        $attributes = [
            'defaultAnonymousPost' => $this->anonymityRepository->defaultValue($serializer->getActor()),
        ];

        if ($serializer->getActor()->hasPermission('anonymous-posting.use')) {
            $attributes['canAnonymousSwitch'] = true;
            // We should also be able to return this value through Extend\Settings::serializeToForum, but it doesn't seem to work
            // might have been because of https://github.com/flarum/framework/issues/3438
            $attributes['anonymousHelpTextPosition'] = $this->settings->get('anonymous-posting.composerHelpTextPosition') ?: 'visible';

            $avatarRules = json_decode((string)$this->settings->get('anonymous-posting.formulaireAvatars'), true);

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
