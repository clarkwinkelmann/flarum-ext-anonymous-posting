<?php

namespace ClarkWinkelmann\AnonymousPosting\Listener;

use Flarum\Settings\Event\Saving;
use Illuminate\Support\Arr;
use Kilowhat\Formulaire\Form;

class SaveSettings
{
    public function handle(Saving $event)
    {
        if (!Arr::exists($event->settings, 'anonymous-posting.formulaireAvatars') || !class_exists(Form::class)) {
            return;
        }

        $avatars = json_decode(Arr::get($event->settings, 'anonymous-posting.formulaireAvatars'), true);

        // Convert string or empty array to null for easier empty setting detection during retrieval
        // The javascript should already take care of this but this way we are extra sure
        if (!is_array($avatars) || count($avatars) === 0) {
            $event->settings['anonymous-posting.formulaireAvatars'] = null;

            return;
        }

        foreach ($avatars as $index => $avatar) {
            $formId = Arr::get($avatar, 'formId');

            // Convert UIDs into IDs because it means one less database request necessary during retrieval
            if (is_string($formId) && strlen($formId) === 36) {
                $avatars[$index]['formId'] = Form::where('uid', $formId)->firstOrFail()->id;
            } else if (preg_match('~[a-zA-Z_-]~', $formId) === 1) {
                // Convert slugs into IDs
                $avatars[$index]['formId'] = Form::where('slug', $formId)->firstOrFail()->id;
            }
        }

        $event->settings['anonymous-posting.formulaireAvatars'] = json_encode($avatars);
    }
}
