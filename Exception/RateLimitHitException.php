<?php

namespace Omnisend\Omnisend\Exception;

class RateLimitHitException extends SynchronizationException
{
    public function __construct(
        $message = "",
        $code = 0,
        \Throwable $previous = null
    ) {
        if (!$message) {
            $message = 'Rate limit hit';
        }
        parent::__construct($message, $code, $previous);
    }
}
