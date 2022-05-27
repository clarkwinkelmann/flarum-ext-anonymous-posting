<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Kilowhat\Formulaire\Submission;

class DiscussionAndPostAttributes
{
    protected SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param BasicDiscussionSerializer|BasicPostSerializer $serializer
     * @param Discussion|Post $model
     * @return array
     */
    public function __invoke(AbstractSerializer $serializer, AbstractModel $model): array
    {
        $attributes = [
            'isAnonymous' => !!$model->anonymous_user_id,
            // Both permission keys need to be included all the time for the frontend to correctly update state after switching
            'canDeAnonymize' => $serializer->getActor()->can('deAnonymize', $model),
            'canAnonymize' => $serializer->getActor()->can('anonymize', $model),
        ];

        if ($attributes['isAnonymous']) {
            $attributes['anonymousAvatarUrl'] = $this->avatarUrl($model);
            $attributes['isAnonymousMe'] = $model->anonymous_user_id === $serializer->getActor()->id;
        }

        // Remove the placeholder user model in case it ends up here, we don't want it in the serializer response
        if ($model->relationLoaded('user') && $model->user instanceof AnonymousUser) {
            $model->unsetRelation('user');
        }

        return $attributes;
    }

    protected function avatarUrl(AbstractModel $model): ?string
    {
        $avatarRules = json_decode($this->settings->get('anonymous-posting.formulaireAvatars'), true);

        // Check if the setting has a value first to avoid degrading performance by retrieving a useless relationship
        // Also skip if Formulaire is not installed to avoid 500 error that would block access to the forum
        if (!$avatarRules || !class_exists(Submission::class)) {
            return null;
        }

        // Ideally we'd want to eager load this relationship, but we can't conditionally add to Flarum's eager loader at the moment
        return AnonymousAvatar::retrieve($avatarRules, $model->anonymousUserSubmissions);
    }
}
