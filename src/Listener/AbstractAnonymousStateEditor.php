<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use ClarkWinkelmann\AnonymousPosting\AnonymityRepository;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Support\Arr;

class AbstractAnonymousStateEditor
{
    protected AnonymityRepository $anonymityRepository;

    public function __construct(AnonymityRepository $anonymityRepository)
    {
        $this->anonymityRepository = $anonymityRepository;
    }

    public function apply(User $actor, AbstractModel $model, array $attributes, string $anonymizedEventClass, string $deAnonymizedEventClass)
    {
        if (!$model->exists) {
            $defaultValue = $this->anonymityRepository->defaultValue($actor);
            $requestsAnonymous = Arr::exists($attributes, 'isAnonymous') ? (bool)Arr::get($attributes, 'isAnonymous') : $defaultValue;

            if ($requestsAnonymous !== $defaultValue) {
                $actor->assertCan('anonymous-posting.use');
            }

            if ($requestsAnonymous) {
                $this->convertToAnonymous($model, $actor);
            }
            // No need to do anything special for new not-anonymous content since that's the Flarum default
        } else if (Arr::exists($attributes, 'isAnonymous')) {
            // In this elseif block, we handle editing the state of an existing post/discussion
            if (Arr::get($attributes, 'isAnonymous')) {
                // If already anonymous, don't change anything
                if ($model->anonymous_user_id) {
                    return;
                }

                $actor->assertCan('anonymize', $model);

                // This will do nothing if $model->user_id is NULL
                // But that case should be caught by the policy so no need for special handling
                $this->convertToAnonymous($model, $model->user_id);

                $model->raise(new $anonymizedEventClass($model, $actor));
            } else {
                // If already de-anonymized, don't change anything
                if (!$model->anonymous_user_id) {
                    return;
                }

                $actor->assertCan('deAnonymize', $model);

                $this->convertToPublic($model);

                $model->raise(new $deAnonymizedEventClass($model, $actor));
            }
        }
    }

    protected function convertToAnonymous(AbstractModel $model, $anonymousUserModelOrId): void
    {
        $model->anonymousUser()->associate($anonymousUserModelOrId);

        // Use dissociate instead of setting user_id to null, that way any loaded relationship is unset,
        // otherwise it might end up in the serializer output if it was read from another event listener
        $model->user()->dissociate();
    }

    protected function convertToPublic(AbstractModel $model)
    {
        $model->user()->associate($model->anonymous_user_id);
        $model->anonymousUser()->dissociate();
    }
}
