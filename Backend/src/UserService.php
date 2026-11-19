<?php

/**
 * CAMADA DE REGRAS DE NEGÓCIO (SERVICE)
 *
 * Aqui ficam as regras: validação, e-mail/login únicos, hash da senha, valor padrão.
 * Não escreve SQL (pede ao Repository) e não responde HTTP (só lança HttpException).
 */
class UserService
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    private function validate($data, $partial)
    {
        $errors = [];

        if (!$partial || array_key_exists('name', $data)) {
            if (!isset($data['name']) || !is_string($data['name']) || mb_strlen(trim($data['name'])) < 3) {
                $errors[] = 'O nome é obrigatório e deve ter no mínimo 3 caracteres.';
            }
        }

        if (!$partial || array_key_exists('email', $data)) {
            if (!isset($data['email']) || !is_string($data['email']) || !filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'email é obrigatório e deve ter formato válido.';
            }
        }

        if (!$partial || array_key_exists('login', $data)) {
            if (!isset($data['login']) || !is_string($data['login']) || mb_strlen(trim($data['login'])) < 3) {
                $errors[] = 'O login é obrigatório e deve ter no mínimo 3 caracteres.';
            }
        }

        if (!$partial || array_key_exists('password', $data)) {
            if (!isset($data['password']) || !is_string($data['password']) || strlen($data['password']) < 8) {
                $errors[] = 'A senha é obrigatório e deve ter no mínimo 8 caracteres.';
            }
        }

        if (array_key_exists('active', $data) && !is_bool($data['active'])) {
            $errors[] = 'active deve ser booleano (true/false).';
        }

        // Se houve algum erro, interrompe tudo com status 400
        if (count($errors) > 0) {
            throw new HttpException(400, implode(' ', $errors));
        }
    }

    // Garante que e-mail e login ainda não pertencem a outro usuário
    private function checkUnique($email, $login, $ignoreId = 0)
    {
        if ($this->repo->emailExists($email, $ignoreId)) {
            throw new HttpException(400, 'E-mail já cadastrado.');
        }
        if ($this->repo->loginExists($login, $ignoreId)) {
            throw new HttpException(400, 'Login já cadastrado.');
        }
    }

    private function handleDbError($e)
    {
        if ($e->getCode() == '23000') { // 23000 = violação de integridade (valor duplicado)
            throw new HttpException(400, 'E-mail ou login já cadastrado.');
        }
        throw $e; // qualquer outro erro continua sendo um erro 500
    }

    public function list()
    {
        return $this->repo->findAll();
    }

    // Busca um usuário; se não existir, lança 404
    public function get($id)
    {
        $user = $this->repo->findById($id);

        if ($user === null) {
            throw new HttpException(404, 'Usuário não encontrado.');
        }
        return $user;
    }

    // Cadastra um usuário novo
    public function create($data)
    {
        $this->validate($data, false);

        $active = true;
        if (isset($data['active'])) {
            $active = $data['active'];
        }

        $user = [
            'name'   => trim($data['name']),
            'email'  => strtolower(trim($data['email'])),
            'login'  => trim($data['login']),
            'active' => $active,
        ];

        $this->checkUnique($user['email'], $user['login']);

        // Nunca guarda a senha pura: só o hash 
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $id = 0;
        try {
            $id = $this->repo->create($user, $hash);
        } catch (PDOException $e) {
            $this->handleDbError($e);
        }

        
        return $this->repo->findById($id);
    }

    // Atualiza um usuário (só os campos enviados)
    public function update($id, $data)
    {
        $user = $this->get($id); 

        $this->validate($data, true);

        if (array_key_exists('name', $data)) {
            $user['name'] = trim($data['name']);
        }
        if (array_key_exists('email', $data)) {
            $user['email'] = strtolower(trim($data['email']));
        }
        if (array_key_exists('login', $data)) {
            $user['login'] = trim($data['login']);
        }
        if (array_key_exists('active', $data)) {
            $user['active'] = $data['active'];
        }

        $this->checkUnique($user['email'], $user['login'], $id);

        try {
            $this->repo->update($id, $user);

            if (array_key_exists('password', $data)) {
                $hash = password_hash($data['password'], PASSWORD_BCRYPT);
                $this->repo->updatePassword($id, $hash);
            }
        } catch (PDOException $e) {
            $this->handleDbError($e);
        }

        return $this->get($id);
    }

    // Exclui um usuário (lança 404 se não existir)
    public function delete($id)
    {
        $this->get($id);
        $this->repo->delete($id);
    }
}
