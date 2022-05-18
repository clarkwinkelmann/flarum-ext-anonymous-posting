<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicDiscussionSerializer;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
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
        }

        return $attributes;
    }

    protected function avatarUrl(AbstractModel $model): ?string
    {
        $avatarRules = json_decode($this->settings->get('anonymous-posting.formulaireAvatars'), true);

        // Check if the setting has a value first to avoid degrading performance by retrieving a useless relationship
        if (!$avatarRules) {
            return null;
        }

        // Ideally we'd want to eager load this relationship, but we can't conditionally add to Flarum's eager loader at the moment
        $submissions = $model->anonymousUserSubmissions->keyBy('form_id');

        foreach ($avatarRules as $rule) {
            $submission = $submissions->get(Arr::get($rule, 'formId'));

            if (!$submission) {
                continue;
            }

            $value = Arr::get($submission->data, Arr::get($rule, 'fieldKey'));

            // Wrap the value into an array, so text-based entries are compared verbatim, while array-based entries are compared entry by entry
            foreach (Arr::wrap($value) as $valueEntry) {
                if ($valueEntry === Arr::get($rule, 'fieldValue')) {
                    return Arr::get($rule, 'avatarUrl');
                }
            }
        }

        return null;
    }
}
