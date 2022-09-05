<?php

namespace Omnisend\Omnisend\Exception;

class TimeLimitExceededException extends SynchronizationException
{
    public function __construct(
        $message = "",
        $code = 0,
        \Throwable $previous = null
    ) {
        if (!$message) {
            $message = 'Time limit exceeded';
        }
        parent::__construct($message, $code, $previous);
    }
}
