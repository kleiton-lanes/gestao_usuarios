<?php
/**
 * CAMADA DE CONEXÃO
 *
 * Única função: abrir a conexão com o SQLite e garantir que a tabela existe.
 * Aqui não há regra de negócio nem resposta HTTP.
 */
class Database
{
    public static function connect()
    {
        // __DIR__ é a pasta deste arquivo (backend/src).
        // Usar __DIR__ faz o caminho ser sempre o mesmo, não importa de onde o servidor foi iniciado.
        $dir  = __DIR__ . '/../database';
        $file = $dir . '/users.sqlite';

        // A pasta "database" fica FORA de "public", então o navegador não consegue baixar o banco.
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Abre (ou cria, se não existir) o arquivo do banco
        $pdo = new PDO('sqlite:' . $file);

        // Quando o SQL der erro, o PDO lança uma exceção (em vez de falhar em silêncio)
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Cada linha do banco volta como array com o nome das colunas: ['id' => 1, 'name' => 'Maria']
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Lê o schema.sql e executa. Como usa "IF NOT EXISTS", só cria a tabela na primeira vez.
        $sql = file_get_contents($dir . '/schema.sql');
        $pdo->exec($sql);

        return $pdo;
    }
}
