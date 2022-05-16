<?php

use Flarum\Database\Migration;

return Migration::addColumns('discussions', [
    // Not a foreign key because we'll use the non-null value to mark as anonymous even if user was deleted
    'anonymous_user_id' => ['integer', 'unsigned' => true, 'nullable' => true, 'index' => true],
]);
