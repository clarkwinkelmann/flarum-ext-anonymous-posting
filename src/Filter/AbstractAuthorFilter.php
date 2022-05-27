<?php

namespace ClarkWinkelmann\AnonymousPosting\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\User\UserRepository;
use Illuminate\Database\Query\Builder;

/**
 * Same as Flarum's Post\Filter\AuthorFilter / Discussion\Filter\AuthorFilter but includes resources created anonymously.
 * Flarum keys the list of filterers by their key in FilterServiceProvider via a loop
 * so this class will override the original provided it's registered afterwards.
 */
abstract class AbstractAuthorFilter implements FilterInterface
{
    protected UserRepository $users;

    protected static string $table = '';

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $usernames = trim($filterValue, '"');
        $usernames = explode(',', $usernames);

        $ids = $this->users->query()->whereIn('username', $usernames)->pluck('id')->toArray();

        $actor = $filterState->getActor();
        // Only allow seeing own anonymous when filtering by a single value, otherwise the SQL would get too complex
        $canSeeAnonymous = (count($ids) === 1 && $ids[0] === $actor->id && !$actor->isGuest()) || $actor->hasPermission('anonymous-posting.reveal');

        $filterState->getQuery()->where(function (Builder $where) use ($ids, $negate, $canSeeAnonymous) {
            $where->whereIn(static::$table . '.user_id', $ids, 'and', $negate);

            if ($canSeeAnonymous) {
                $where->whereIn(static::$table . '.anonymous_user_id', $ids, $negate ? 'and' : 'or', $negate);
            }
        });
    }
}
