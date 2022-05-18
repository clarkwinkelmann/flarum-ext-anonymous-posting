<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class AnonymousAvatar
{
    public static function retrieve(array $avatarRules, Collection $submissions): ?string
    {
        $submissionsByFormId = $submissions->keyBy('form_id');

        foreach ($avatarRules as $rule) {
            $submission = $submissionsByFormId->get(Arr::get($rule, 'formId'));

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
