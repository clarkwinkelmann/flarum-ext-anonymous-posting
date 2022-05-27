<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\User\Guest;

/**
 * To be used as a workaround for code that can't handle null ->user relationships,
 * like the subject and blade templates of both mentions notifications
 */
class AnonymousUser extends Guest
{
    // ID needs to be NULL because it ends up in notifications.from_user_id column via getFromUser()
    public $id = null;

    public function getDisplayNameAttribute()
    {
        return resolve('translator')->trans('clarkwinkelmann-anonymous-posting.lib.userMeta.username');
    }
}
