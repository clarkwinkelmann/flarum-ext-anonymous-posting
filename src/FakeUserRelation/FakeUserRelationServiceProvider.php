<?php

namespace ClarkWinkelmann\AnonymousPosting\FakeUserRelation;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Bus\Dispatcher;

class FakeUserRelationServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Workaround subject/blade notifications trying to access $post->user->display_name of anonymous posts
        // Doing this in the NotifyMentionWhenVisible listener would only fix post mentions to anonymous posts,
        // but not post mentions to regular posts or user mentions since those have already been handled at that point
        // We try to do this as late as possible to reduce the risk of breaking other extension's logic which cannot handle $post->user being guest
        // We must use a dispatcher pipe and cannot use the queue dispatching event because the final job hasn't been unserialized yet
        // And we need the final objects that will be used in the job and views since we are going to modify them by reference
        // We also cannot use view composers/creators because that doesn't cover the email title which are generated earlier
        $this->container->extend(Dispatcher::class, function (Dispatcher $dispatcher) {
            /**
             * @var DispatcherPipeManipulator $manipulator
             */
            $manipulator = $this->container->make(DispatcherPipeManipulator::class);

            $manipulator->addPipesToExistingDispatcher($dispatcher, [
                FakeUserHydratorPipe::class,
            ]);

            return $dispatcher;
        });
    }
}
