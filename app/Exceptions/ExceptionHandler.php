<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Responses\Response;
use Monolog\Logger;
use Throwable;

final class ExceptionHandler
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $exception): Response
    {
        $this->logger->error($exception->getMessage(), [
            'exception' => $exception,
        ]);

        return Response::error(
            'internal_server_error',
            'An unexpected error occurred.',
            500
        );
    }
}
