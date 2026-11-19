<?php
declare(strict_types=1);

/**
 * CAMADA DE EXCEÇÕES HTTP
 * O service lança (ex.: 400, 404) e o index.php converte em resposta JSON.
 */
class HttpException extends Exception
{
    public function __construct(public int $status, string $message)
    {
        parent::__construct($message);
    }
}
