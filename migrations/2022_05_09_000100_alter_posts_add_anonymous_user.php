<?php

use Flarum\Database\Migration;

return Migration::addColumns('posts', [
    // Same format as in discussions
    'anonymous_user_id' => ['integer', 'unsigned' => true, 'nullable' => true, 'index' => true],
]);
