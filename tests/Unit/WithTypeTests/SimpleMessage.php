<?php

namespace Rebing\GraphQL\Tests\Unit\WithTypeTests;

class SimpleMessage
{
    public $message;
    public $type;
    public $code;

    /**
     * Create a message to return to the users in frontend.
     *
     * @param string $message message to show
     * @param string $type    MessageType enum value
     */
    public function __construct(
        string $message,
        string $type = 'success'
    ) {
        $this->message = $message;
        $this->type = $type;
    }
}
