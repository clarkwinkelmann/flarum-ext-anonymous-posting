<?php

namespace ClarkWinkelmann\AnonymousPosting\FakeUserRelation;

use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationMailer;
use Flarum\User\User;

/**
 * Shenanigans to extract the private $blueprint property out of SendEmailNotificationJob together with the JobProcessing event
 * We don't really care about the constructor parameters, we leave them untouched to reduce the risk of Flarum breaking changes
 * We'll just resolve this class through the container to account for any parent constructor parameter
 */
class EmailNotificationJobBlueprintExtractor extends NotificationMailer
{
    protected MailableInterface $blueprint;

    public function send(MailableInterface $blueprint, User $user)
    {
        // No-op send, just save a copy of the blueprint
        $this->blueprint = $blueprint;
    }

    public function getBlueprint(): MailableInterface
    {
        return $this->blueprint;
    }
}
