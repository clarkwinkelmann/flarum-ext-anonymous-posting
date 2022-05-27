<?php

namespace ClarkWinkelmann\AnonymousPosting\Provider;

use ClarkWinkelmann\AnonymousPosting\Filter\DiscussionAuthorFilter;
use ClarkWinkelmann\AnonymousPosting\Filter\PostAuthorFilter;
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Discussion\Query\AuthorFilterGambit;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Filter\AuthorFilter;
use Flarum\Post\Filter\PostFilterer;

class FilterServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // We don't use the extender because in addition to injecting new author filters, we also want to remove the originals
        $this->container->extend('flarum.filter.filters', function ($originalFilters) {
            $originalFilters[DiscussionFilterer::class] = array_diff(
                    $originalFilters[DiscussionFilterer::class],
                    [AuthorFilterGambit::class]) + [ // Class to remove
                    DiscussionAuthorFilter::class, // Class to add at the end
                ];

            $originalFilters[PostFilterer::class] = array_diff(
                    $originalFilters[PostFilterer::class],
                    [AuthorFilter::class]) + [
                    PostAuthorFilter::class,
                ];

            return $originalFilters;
        });
    }
}
