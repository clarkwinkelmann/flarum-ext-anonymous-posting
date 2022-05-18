<?php

namespace ClarkWinkelmann\AnonymousPosting;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;

class IncludeAnonymousUserRelation
{
    protected string $prefix = '';

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
    }

    public function __invoke(AbstractSerializeController $controller, $data, ServerRequestInterface $request)
    {
        if (!RequestUtil::getActor($request)->hasPermission('anonymous-posting.reveal')) {
            return;
        }

        $controller->addInclude($this->prefix . 'anonymousUser');
    }
}
