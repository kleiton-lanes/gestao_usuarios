<?php

/**
 * CAMADA HTTP (CONTROLLER)
 *
 * Recebe o método HTTP (GET, POST...), o id da URL e o corpo JSON,
 * chama o Service e devolve [status HTTP, dados].
 * Não tem regra de negócio.
 */
class UserController
{
    private $service;

    public function __construct($service)
    {
        $this->service = $service;
    }

    // $method = GET, POST, PUT, PATCH ou DELETE
    // $id     = o que vier depois de /users/ (ou null quando a URL é só /users)
    public function handle($method, $id)
    {
        // ---------- Rotas SEM id: /users ----------
        if ($id === null) {
            if ($method === 'GET') {
                return [200, $this->service->list()];
            }
            if ($method === 'POST') {
                return [201, $this->service->create($this->readBody())];
            }
            throw new HttpException(405, 'Método não permitido.');
        }

        // ---------- Rotas COM id: /users/{id} ----------
        // Se o id não for um número (ex.: /users/abc), usamos 0, que nunca existe e gera 404
        if (ctype_digit($id)) {
            $id = (int) $id;
        } else {
            $id = 0;
        }

        if ($method === 'GET') {
            return [200, $this->service->get($id)];
        }
        if ($method === 'PUT' || $method === 'PATCH') {
            return [200, $this->service->update($id, $this->readBody())];
        }
        if ($method === 'DELETE') {
            $this->service->delete($id);
            return [204, null]; // 204 = sucesso, sem corpo
        }

        throw new HttpException(405, 'Método não permitido.');
    }

    // Lê o corpo da requisição (JSON) e transforma em array do PHP
    private function readBody()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Se o JSON for inválido, json_decode devolve null
        if (!is_array($data)) {
            throw new HttpException(400, 'JSON inválido.');
        }
        return $data;
    }
}
