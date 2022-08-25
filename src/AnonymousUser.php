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

    protected function newRelatedInstance($class)
    {
        // Trying to call relationships on the guest/anonymous user results in SQL errors with table names auto-generated from class names and relations
        throw new \Exception('[anonymous-posting] Eloquent relationship called on AnonymousUser instance. This is not supported. You probably have an incompatible extension.');
    }
}
