<?php

/**
 * CAMADA DE PERSISTÊNCIA (REPOSITORY)
 *
 * É o ÚNICO arquivo que escreve SQL.
 * Só guarda e busca dados: não valida nada e não conhece HTTP.
 */
class UserRepository
{
    private $pdo;

    // O PDO (conexão com o banco) chega pronto de fora
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Colunas que podem ser devolvidas. O password_hash fica DE FORA de propósito,
    // assim a senha nunca aparece no JSON.
    private $columns = 'id, name, email, login, active';

    // O SQLite devolve tudo como texto/número. Aqui convertemos para os tipos certos:
    // id vira inteiro e active (1 ou 0) vira true ou false.
    private function formatUser($row)
    {
        $row['id'] = (int) $row['id'];
        $row['active'] = (bool) $row['active'];
        return $row;
    }

    // Devolve a lista de todos os usuários
    public function findAll()
    {
        $rows = $this->pdo->query('SELECT ' . $this->columns . ' FROM users ORDER BY id')->fetchAll();

        $users = [];
        foreach ($rows as $row) {
            $users[] = $this->formatUser($row);
        }
        return $users;
    }

    // Devolve um usuário pelo id, ou null se não existir
    public function findById($id)
    {
        // Os ":id" são "prepared statements": o valor vai separado do SQL.
        // Isso impede SQL Injection.
        $stmt = $this->pdo->prepare('SELECT ' . $this->columns . ' FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null; // não achou
        }
        return $this->formatUser($row);
    }

    // Verifica se o e-mail já existe em OUTRO usuário.
    // $ignoreId serve na edição: o próprio usuário não conta como duplicado.
    public function emailExists($email, $ignoreId = 0)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
        $stmt->execute(['email' => $email, 'id' => $ignoreId]);
        return $stmt->fetchColumn() > 0;
    }

    // Verifica se o login já existe em OUTRO usuário (sem diferenciar maiúsculas, por causa do COLLATE NOCASE)
    public function loginExists($login, $ignoreId = 0)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE login = :login AND id <> :id');
        $stmt->execute(['login' => $login, 'id' => $ignoreId]);
        return $stmt->fetchColumn() > 0;
    }

    // Insere um usuário e devolve o id gerado pelo banco
    public function create($user, $passwordHash)
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, login, password_hash, active)
             VALUES (:name, :email, :login, :hash, :active)'
        );
        $stmt->execute([
            'name'   => $user['name'],
            'email'  => $user['email'],
            'login'  => $user['login'],
            'hash'   => $passwordHash,
            'active' => $user['active'] ? 1 : 0, // o SQLite guarda true/false como 1/0
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    // Atualiza os dados de um usuário (sem mexer na senha)
    public function update($id, $user)
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET name = :name, email = :email, login = :login, active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'name'   => $user['name'],
            'email'  => $user['email'],
            'login'  => $user['login'],
            'active' => $user['active'] ? 1 : 0,
            'id'     => $id,
        ]);
    }

    // Troca só a senha (já recebe o hash pronto)
    public function updatePassword($id, $passwordHash)
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    // Exclusão FÍSICA: a linha some do banco
    public function delete($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
