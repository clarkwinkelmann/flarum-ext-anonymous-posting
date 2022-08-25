<?php

namespace ClarkWinkelmann\AnonymousPosting\FakeUserRelation;

use Illuminate\Bus\Dispatcher;

/**
 * The Dispatcher doesn't have a method to *add* pipes, so we need to hook into it to copy any existing pipe when we set the new ones
 */
class DispatcherPipeManipulator extends Dispatcher
{
    public function addPipesToExistingDispatcher(Dispatcher $dispatcher, array $pipes): void
    {
        $dispatcher->pipes += $pipes;
    }
}
