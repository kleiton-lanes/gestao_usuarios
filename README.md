# Gestão de Usuários

# Gestão de Usuários — CRUD Full-Stack

Aplicação full-stack de gestão de usuários desenvolvida para o Desafio Técnico.

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Frontend | **Vue.js 3** (via CDN, sem build) + HTML + CSS |
| Backend | **PHP 8.1+** (PDO, sem framework) |
| Banco de dados | **SQLite** (arquivo local, criado automaticamente) |
| Hash de senha | **BCrypt** (`password_hash`) |

## Estrutura do projeto

```
gestao-usuarios/
├── backend/
│   ├── public/index.php           # Entrada da API: CORS, rotas, tratamento de erros
│   ├── src/
│   │   ├── Database.php           # Conexão PDO + criação da tabela
│   │   ├── UserRepository.php     # SQL (única camada que fala com o banco)
│   │   ├── UserService.php        # Regras de negócio, validação, hash de senha
│   │   ├── UserController.php     # Traduz HTTP (método, id, JSON) em chamadas ao service
│   │   └── HttpException.php      # Erro com status HTTP (400, 404...)
│   └── database/
│       ├── schema.sql             # Estrutura da tabela users
│       └── users.sqlite           # Banco (criado na 1ª execução, fora do Git)
└── frontend/
    ├── index.html                 # Template Vue (formulário + tabela)
    ├── js/api.js                  # Único arquivo que usa fetch / conhece a URL da API
    ├── js/app.js                  # Estado e ações da tela (Vue)
    └── css/style.css
```

### Fluxo de uma requisição (do clique à gravação)

```
Clique em "Cadastrar" → app.js (save) → api.js (fetch POST /users)
  → index.php (CORS + rota) → UserController → UserService (valida + hash)
  → UserRepository (INSERT) → SQLite
  → volta: 201 + JSON (sem senha) → app.js mostra "sucesso" e recarrega a lista
```

## Como executar

### Pré-requisitos
- PHP 8.1 ou superior com as extensões `pdo_sqlite` e `sqlite3` ativas (confira com `php -m`)
- Navegador com acesso à internet (o Vue é carregado por CDN)

Não é preciso instalar banco de dados, Apache ou Composer.

### 1. Backend (porta 3000)

```bash
cd backend
php -S localhost:3000 -t public
```

O arquivo `backend/database/users.sqlite` e a tabela `users` são criados automaticamente na primeira requisição.

### 2. Frontend (porta 5173)

Em **outro terminal**:

```bash
cd frontend
php -S localhost:5173
```

Acesse **http://localhost:5173**.

> O frontend e a API rodam em portas diferentes, por isso o CORS está habilitado no backend.
> Se mudar a porta da API, ajuste `API_URL` em `frontend/js/api.js`.

### Problemas comuns

| Sintoma | Solução |
|---|---|
| `could not find driver` | Ative `extension=pdo_sqlite` e `extension=sqlite3` no `php.ini` (`php --ini` mostra qual arquivo está em uso). |
| `Address already in use` | Já há um servidor rodando nessa porta. Feche-o com `Ctrl+C` ou use outra porta. |
| `Directory ... does not exist` | O terminal não está na pasta `backend` (ou `frontend`). |
| Tela mostra "Não foi possível conectar ao servidor" | O backend não está rodando na porta 3000. |

## Endpoints

| Método | Rota | Sucesso | Erros |
|---|---|---|---|
| POST | `/users` | 201 Created | 400 |
| GET | `/users` | 200 OK | — |
| GET | `/users/{id}` | 200 OK | 404 |
| PUT / PATCH | `/users/{id}` | 200 OK | 400, 404 |
| DELETE | `/users/{id}` | 204 No Content | 404 |

PUT e PATCH têm o mesmo comportamento: atualização **parcial** (só valida e altera os campos enviados).

### Formato do JSON

**Requisição (POST):**
```json
{
  "name": "Maria Silva",
  "email": "maria@email.com",
  "login": "maria",
  "password": "senha1234",
  "active": true
}
```
`active` é opcional (padrão `true`).

**Resposta (a senha e o hash nunca são devolvidos):**
```json
{ "id": 1, "name": "Maria Silva", "email": "maria@email.com", "login": "maria", "active": true }
```

**Erro (400/404):**
```json
{ "error": "name é obrigatório e deve ter no mínimo 3 caracteres." }
```

## Exemplos com cURL

No PowerShell do Windows, use `curl.exe` (e não apenas `curl`). Em CMD, Linux e macOS, `curl` funciona normalmente.

```bash
# Criar
curl -i -X POST http://localhost:3000/users -H "Content-Type: application/json" -d "{\"name\":\"Maria Silva\",\"email\":\"maria@email.com\",\"login\":\"maria\",\"password\":\"senha1234\"}"

# Listar todos
curl -i http://localhost:3000/users

# Buscar um (200) e um inexistente (404)
curl -i http://localhost:3000/users/1
curl -i http://localhost:3000/users/999

# Atualizar
curl -i -X PATCH http://localhost:3000/users/1 -H "Content-Type: application/json" -d "{\"name\":\"Maria S. Souza\",\"active\":false}"

# Excluir
curl -i -X DELETE http://localhost:3000/users/1

# Payload inválido (400)
curl -i -X POST http://localhost:3000/users -H "Content-Type: application/json" -d "{\"name\":\"Jo\",\"email\":\"invalido\",\"login\":\"a\",\"password\":\"123\"}"
```

## Regras de negócio implementadas

- **ID** gerado pelo banco (`AUTOINCREMENT`).
- **name** e **login**: obrigatórios, mínimo de 3 caracteres (após `trim`).
- **email**: obrigatório, formato válido, único (normalizado para minúsculas).
- **login**: único, sem diferenciar maiúsculas de minúsculas (`COLLATE NOCASE`).
- **password**: obrigatório, mínimo de 8 caracteres, armazenado com **BCrypt**.
- **active**: booleano, padrão `true` (no SQLite é guardado como `1`/`0`).
- **Payload inválido** → `400` com JSON explicativo (inclui JSON malformado).
- **Duplicidade**: verificada no service **e** garantida por `UNIQUE` no banco (proteção contra requisições simultâneas).
- **Isolamento de camadas**: o frontend só conversa com a API REST; a API separa controller, service e repository.

## Decisões técnicas

- **SQLite**: não exige instalar nem configurar servidor, e o projeto roda em qualquer máquina com PHP. Como o SQL fica só no repository, trocar de banco afetaria praticamente só a conexão.
- **Banco fora de `public/`**: o servidor só expõe a pasta `public`, então o arquivo `.sqlite` não pode ser baixado pelo navegador.
- **Exclusão física (`DELETE`)**: atende ao requisito com menos complexidade. Com exclusão lógica, o e-mail e o login de um usuário "apagado" continuariam ocupados por causa do `UNIQUE`. Soft delete seria a escolha se o sistema exigisse histórico/auditoria.
- **Duplicidade retorna 400**: o desafio define que violações das regras de validação retornam `400`. (`409 Conflict` seria semanticamente mais preciso.)
- **SQL com prepared statements** (PDO): evita SQL Injection.
- **`password_hash` fora do SELECT**: as consultas nunca incluem a coluna do hash, então ela não tem como vazar no JSON.
- **Frontend sem `v-html`**: o Vue escapa o conteúdo das chaves `{{ }}`, evitando XSS.
- **Vue via CDN**: reduz dependências e facilita a execução; a lógica está separada em `api.js` (comunicação) e `app.js` (tela)
