<?php

/**
 * PORTA DE ENTRADA DA API
 *
 * Toda requisição passa por aqui. Este arquivo:
 *   1) libera o CORS;
 *   2) descobre a rota (/users ou /users/{id});
 *   3) monta as camadas e chama o Controller;
 *   4) transforma qualquer erro em uma resposta JSON.
 */

require_once __DIR__ . '/../src/HttpException.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/UserService.php';
require_once __DIR__ . '/../src/UserController.php';

// ---------- CORS ----------
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Define o status HTTP, escreve o JSON (se houver) e encerra o script
function respond($status, $data = null)
{
    http_response_code($status);
    if ($data !== null) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Antes de POST/PATCH/DELETE, o navegador manda uma pergunta "posso?" (método OPTIONS).
if ($method === 'OPTIONS') {
    respond(204);
}

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if (!preg_match('#^/users(?:/([^/]+))?/?$#', $path, $matches)) {
        throw new HttpException(404, 'Rota não encontrada.');
    }

    $id = null;
    if (isset($matches[1])) {
        $id = $matches[1];
    }

    // Monta as camadas, de baixo para cima: banco -> repository -> service -> controller
    $pdo        = Database::connect();
    $repository = new UserRepository($pdo);
    $service    = new UserService($repository);
    $controller = new UserController($service);

    $result = $controller->handle($method, $id);
    respond($result[0], $result[1]);

} catch (HttpException $e) {
    // Erros "esperados" (400, 404, 405): devolvem a mensagem para o cliente
    respond($e->status, ['error' => $e->getMessage()]);

} catch (Throwable $e) {
    // Erro inesperado: registra no log do servidor e responde 500 sem expor detalhes
    error_log($e->getMessage());
    respond(500, ['error' => 'Erro interno do servidor.']);
}
