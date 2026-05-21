# gestor-de-pagamentos — Guia: Construindo uma API REST com PHP 8.2 e Laravel 11

Este guia acompanha a construção de uma API de gerenciamento de clientes e faturas do zero, usando Laravel 11 como framework e o Blueprint para acelerar a geração de código. Ao final, você terá uma API funcional com endpoints para criar, listar, atualizar e deletar clientes e faturas, conectada a um banco de dados PostgreSQL.

O projeto segue a arquitetura MVC do Laravel, complementada pela camada de Services para isolar as regras de negócio. Cada seção explica não só o que fazer, mas por que cada decisão foi tomada.

---

## O que vamos construir

Uma API REST com os seguintes recursos:

- **Clientes** — cadastro e gerenciamento de clientes com nome, email e telefone
- **Faturas** — criação e listagem de faturas vinculadas a clientes, com valor, vencimento e status

---

## Sumário

1. [Pre-requisitos](#1-pre-requisitos)
2. [Criando o projeto](#2-criando-o-projeto)
3. [Configurando o banco de dados](#3-configurando-o-banco-de-dados)
4. [Instalando o Blueprint](#4-instalando-o-blueprint)
5. [Gerando os componentes com Blueprint](#5-gerando-os-componentes-com-blueprint)
6. [Entendendo o código gerado](#6-entendendo-o-código-gerado)
7. [Criando a camada de Services](#7-criando-a-camada-de-services)
8. [Ajustando os Controllers](#8-ajustando-os-controllers)
9. [Rodando as migrations e testando](#9-rodando-as-migrations-e-testando)
10. [Tratamento de erros](#10-tratamento-de-erros)
11. [Testando com Postman](#11-testando-com-postman)
12. [Próximos passos: autenticação com Sanctum](#12-próximos-passos-autenticação-com-sanctum)

---

## 1. Pre-requisitos

### O que você vai precisar e por que

| Ferramenta | Para que serve |
|---|---|
| PHP 8.2+ | A linguagem em que o Laravel é escrito. O Laravel 11 exige no mínimo PHP 8.2. |
| Composer | Gerenciador de dependências do PHP. Equivalente ao Maven no Java — ele baixa e gerencia os pacotes que o projeto usa. |
| PostgreSQL 16 | O banco de dados relacional onde os dados serão persistidos. |
| VS Code ou PhpStorm | A IDE onde você vai escrever o código. O PhpStorm tem suporte nativo a PHP e Laravel; o VS Code precisa da extensão PHP Intelephense. |
| Postman | Ferramenta visual para fazer requisições HTTP e testar os endpoints. |

### Instalação

- [PHP 8.2+](https://www.php.net/downloads) — no Windows, use o [XAMPP](https://www.apachefriends.org/) ou o [Laragon](https://laragon.org/); no macOS, use `brew install php`
- [Composer](https://getcomposer.org/download/)
- [PostgreSQL 16](https://www.postgresql.org/download/)
- [VS Code](https://code.visualstudio.com/) com extensão [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client) ou [PhpStorm](https://www.jetbrains.com/phpstorm/)
- [Postman](https://www.postman.com/)

Após instalar, verifique se tudo está funcionando:

```bash
php -v        # deve mostrar 8.2.x ou superior
composer -V
psql --version
```

---

## 2. Criando o projeto

### O que é o Laravel

Laravel é o framework PHP mais popular para desenvolvimento web. Ele segue o padrão MVC (Model-View-Controller) e oferece, de fábrica, um ORM (Eloquent), sistema de migrations, filas, autenticação, e muito mais — tudo com convenções que eliminam boa parte do código repetitivo.

### Criando o projeto com Composer

```bash
composer create-project laravel/laravel gestor-de-pagamentos "11.*"
cd gestor-de-pagamentos
```

O comando `create-project` baixa o Laravel e instala todas as dependências automaticamente. Ao final, você terá a seguinte estrutura:

```
gestor-de-pagamentos/
├── app/
│   ├── Http/
│   │   ├── Controllers/    ← controllers ficam aqui
│   │   └── Requests/       ← validações de formulário
│   └── Models/             ← modelos Eloquent (entidades)
├── database/
│   ├── factories/          ← factories para testes
│   └── migrations/         ← histórico de alterações no banco
├── routes/
│   └── api.php             ← rotas da API
├── .env                    ← variáveis de ambiente (banco, chaves etc.)
└── composer.json           ← dependências do projeto
```

### Inicie o servidor de desenvolvimento

```bash
php artisan serve
```

Acesse `http://localhost:8000`. Se aparecer a tela inicial do Laravel, a instalação funcionou. Encerre com `Ctrl+C` antes de continuar.

> `artisan` é a ferramenta de linha de comando do Laravel. Você vai usá-la para praticamente tudo: gerar arquivos, rodar migrations, limpar cache etc.

---

## 3. Configurando o banco de dados

### Criando o banco no PostgreSQL

```bash
psql -U postgres
```

```sql
CREATE DATABASE gestor_de_pagamentos;
\q
```

### Conectando a aplicação ao banco

O arquivo `.env` na raiz do projeto armazena todas as variáveis de ambiente — credenciais de banco, chaves de API, configurações de e-mail etc. Ele nunca deve ser versionado no Git (já está no `.gitignore` por padrão).

Abra o `.env` e ajuste as variáveis de banco:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gestor_de_pagamentos
DB_USERNAME=postgres
DB_PASSWORD=sua_senha_aqui
```

### Habilitando as rotas de API

No Laravel 11, as rotas de API não vêm habilitadas por padrão. Execute:

```bash
php artisan install:api
```

Esse comando cria o arquivo `routes/api.php` e configura o middleware correto para ele. Todas as rotas definidas nesse arquivo ficam automaticamente sob o prefixo `/api`.

### Verifique a conexão

```bash
php artisan migrate
```

Se o banco estiver configurado corretamente, as tabelas padrão do Laravel (`users`, `cache`, `jobs`) serão criadas. Se aparecer erro de conexão, verifique se o PostgreSQL está rodando e se as credenciais no `.env` estão corretas.

---

## 4. Instalando o Blueprint

### O que é o Blueprint

Blueprint é um pacote open-source que gera múltiplos componentes Laravel a partir de um único arquivo YAML. Em vez de rodar `php artisan make:model`, `php artisan make:migration`, `php artisan make:controller`, `php artisan make:request` separadamente — e depois escrever o código de cada um manualmente — você descreve seus modelos e controllers em um arquivo `draft.yaml` e o Blueprint gera tudo de uma vez.

A partir de uma definição simples, o Blueprint é capaz de gerar:

- Model com `$fillable`, `$casts` e relacionamentos
- Migration com todas as colunas
- Factory com dados falsos para testes
- Controller com actions completas
- Form Requests com validações
- Testes HTTP automatizados

### Instalação

O Blueprint é uma dependência de desenvolvimento — você só precisa dele para gerar código, não em produção.

```bash
composer require --dev laravel-shift/blueprint
```

Também é recomendado instalar o pacote de assertions extras para os testes gerados:

```bash
composer require --dev jasonmccreary/laravel-test-assertions
```

Verifique se o Blueprint foi instalado:

```bash
php artisan blueprint:new
```

Esse comando cria o arquivo `draft.yaml` na raiz do projeto — é ele que você vai editar para definir seus componentes.

### Arquivos do Blueprint no .gitignore

O Blueprint usa alguns arquivos auxiliares que não precisam ser versionados. Adicione ao `.gitignore`:

```
.blueprint
```

O `draft.yaml` em si pode ser versionado — ele serve como documentação viva da estrutura do projeto.

---

## 5. Gerando os componentes com Blueprint

### O arquivo draft.yaml

O `draft.yaml` é onde você descreve a estrutura da sua aplicação. Abra o arquivo gerado pelo `blueprint:new` e substitua o conteúdo por:

```yaml
models:
  Cliente:
    nome: string
    email: string unique
    telefone: string nullable
    relationships:
      hasMany: Fatura

  Fatura:
    cliente_id: id:cliente
    valor: decimal:10,2
    vencimento: date
    status: string
    relationships:
      belongsTo: Cliente

controllers:
  Cliente:
    index:
      query: all
      respond: clientes
    show:
      find: cliente
      respond: cliente
    store:
      validate: nome, email, telefone
      save: cliente
      respond: cliente 201
    update:
      find: cliente
      validate: nome, email, telefone
      update: cliente
      respond: cliente
    destroy:
      find: cliente
      delete: cliente
      respond: 204

  Fatura:
    index:
      query: all
      respond: faturas
    show:
      find: fatura
      respond: fatura
    store:
      validate: cliente_id, valor, vencimento, status
      save: fatura
      respond: fatura 201
    destroy:
      find: fatura
      delete: fatura
      respond: 204
```

### Entendendo a sintaxe do draft.yaml

**Seção `models`**

Cada modelo começa com o nome em `StudlyCase` singular (ex: `Cliente`, não `clientes`). As colunas são pares `nome: tipo`, usando os mesmos tipos do Laravel Schema Builder.

Modificadores como `nullable` e `unique` são adicionados inline. O Blueprint gera automaticamente as colunas `id`, `created_at` e `updated_at` — você não precisa defini-las.

O tipo `id:cliente` é um atalho do Blueprint para criar uma chave estrangeira (`cliente_id`) referenciando a tabela `clientes`.

**Seção `controllers`**

Cada action dentro de um controller mapeia para um método. Os statements dentro de cada action descrevem o que aquele método deve fazer:

| Statement | O que gera |
|---|---|
| `query: all` | `$clientes = Cliente::all();` |
| `find: cliente` | `$cliente = Cliente::findOrFail($id);` |
| `validate: campos` | cria um `FormRequest` com as regras de validação |
| `save: cliente` | `$cliente->save();` |
| `update: cliente` | `$cliente->update($request->validated());` |
| `delete: cliente` | `$cliente->delete();` |
| `respond: cliente 201` | `return response()->json($cliente, 201);` |

### Gerando os componentes

Com o `draft.yaml` pronto, execute:

```bash
php artisan blueprint:build
```

O Blueprint vai gerar e listar todos os arquivos criados:

```
Created:
- database/migrations/2026_05_21_000001_create_clientes_table.php
- database/migrations/2026_05_21_000002_create_faturas_table.php
- app/Models/Cliente.php
- app/Models/Fatura.php
- database/factories/ClienteFactory.php
- database/factories/FaturaFactory.php
- app/Http/Controllers/ClienteController.php
- app/Http/Controllers/FaturaController.php
- app/Http/Requests/StoreClienteRequest.php
- app/Http/Requests/UpdateClienteRequest.php
- app/Http/Requests/StoreFaturaRequest.php
- routes/api.php (updated)
- tests/Feature/Http/Controllers/ClienteControllerTest.php
- tests/Feature/Http/Controllers/FaturaControllerTest.php
```

> Se precisar desfazer tudo e recomeçar, use `php artisan blueprint:erase`. Isso remove todos os arquivos gerados pelo último build.

---

## 6. Entendendo o código gerado

Vale a pena abrir e entender os arquivos que o Blueprint criou antes de continuar.

### O Model

```php
// app/Models/Cliente.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    // $fillable define quais colunas podem ser preenchidas via mass assignment
    // (ex: Cliente::create($request->all())).
    // É uma proteção contra ataques que enviam campos extras na requisição.
    protected $fillable = [
        'nome',
        'email',
        'telefone',
    ];

    // O relacionamento com Fatura:
    // Um cliente tem muitas faturas.
    public function faturas()
    {
        return $this->hasMany(Fatura::class);
    }
}
```

### A Migration

```php
// database/migrations/xxxx_create_clientes_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();                            // coluna id auto-increment
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('telefone')->nullable();
            $table->timestamps();                    // created_at e updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');            // desfaz a migration
    }
};
```

> Migrations são como um histórico de versões do banco de dados. Cada migration representa uma alteração. O método `up()` aplica a mudança e `down()` a reverte. Isso permite que a equipe toda tenha sempre o mesmo schema.

### O Form Request

```php
// app/Http/Requests/StoreClienteRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    // authorize() define se o usuário tem permissão para fazer esta requisição.
    // Por enquanto, retorna true (qualquer um pode). Aqui é onde entraria a lógica
    // de autorização quando adicionarmos autenticação.
    public function authorize(): bool
    {
        return true;
    }

    // rules() define as regras de validação para cada campo.
    // O Laravel valida automaticamente antes de chegar no controller.
    // Se alguma regra falhar, retorna 422 Unprocessable Entity com os erros.
    public function rules(): array
    {
        return [
            'nome'     => ['required', 'string'],
            'email'    => ['required', 'string', 'email', 'unique:clientes'],
            'telefone' => ['nullable', 'string'],
        ];
    }
}
```

### O Controller gerado

```php
// app/Http/Controllers/ClienteController.php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::all();
        return response()->json($clientes);
    }

    public function store(StoreClienteRequest $request)
    {
        $cliente = Cliente::create($request->validated());
        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());
        return response()->json($cliente);
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(null, 204);
    }
}
```

> Note o `Cliente $cliente` nos métodos `show`, `update` e `destroy`. Isso é **Route Model Binding**: o Laravel recebe o `{id}` da URL, busca o `Cliente` correspondente automaticamente e já injeta o objeto. Se não encontrar, retorna `404` sem você precisar escrever nada.

### As rotas geradas

> **Atenção — comportamento conhecido do Blueprint:** o Blueprint pode adicionar as rotas no `routes/web.php` em vez do `routes/api.php`. Se isso acontecer, as rotas vão aparecer sem o prefixo `/api/` ao rodar `php artisan route:list`. Para corrigir, remova as linhas de `clientes` e `faturas` do `web.php` e adicione manualmente no `api.php`.

O `routes/api.php` deve ficar assim:

```php
<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\FaturaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('clientes', ClienteController::class);
Route::apiResource('faturas', FaturaController::class);
```

Após qualquer alteração nas rotas, limpe o cache:

```bash
php artisan optimize:clear
php artisan route:list
```

`apiResource` é um atalho que registra todas as rotas RESTful de uma vez:

| Método | URI | Action |
|---|---|---|
| GET | `/api/clientes` | index |
| POST | `/api/clientes` | store |
| GET | `/api/clientes/{cliente}` | show |
| PUT/PATCH | `/api/clientes/{cliente}` | update |
| DELETE | `/api/clientes/{cliente}` | destroy |

---

## 7. Criando a camada de Services

O código gerado pelo Blueprint já está funcional, mas toda a lógica fica diretamente no controller. Para projetos maiores, é recomendado separar as regras de negócio em uma camada de Services — assim o controller fica responsável apenas por receber a requisição e devolver a resposta.

### Por que usar Services

Em uma arquitetura sem services, se você precisar da mesma lógica em dois controllers diferentes (ou em um comando Artisan, ou em um job), vai ter que duplicar código. O service é uma classe PHP simples que encapsula a lógica e pode ser reutilizada em qualquer lugar.

### Criando os Services

O Laravel não gera services automaticamente — crie a pasta e os arquivos manualmente:

```bash
mkdir app/Services
```

```php
// app/Services/ClienteService.php
<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;

class ClienteService
{
    public function listarTodos(): Collection
    {
        return Cliente::all();
    }

    public function buscarPorId(int $id): Cliente
    {
        // findOrFail lança ModelNotFoundException se não encontrar,
        // que o Laravel converte automaticamente em resposta 404.
        return Cliente::findOrFail($id);
    }

    public function criar(array $dados): Cliente
    {
        return Cliente::create($dados);
    }

    public function atualizar(Cliente $cliente, array $dados): Cliente
    {
        $cliente->update($dados);
        return $cliente->fresh(); // recarrega o modelo do banco após o update
    }

    public function deletar(Cliente $cliente): void
    {
        $cliente->delete();
    }
}
```

```php
// app/Services/FaturaService.php
<?php

namespace App\Services;

use App\Models\Fatura;
use Illuminate\Database\Eloquent\Collection;

class FaturaService
{
    public function listarTodas(): Collection
    {
        return Fatura::with('cliente')->get();
        // with('cliente') faz eager loading: carrega o cliente junto com cada fatura
        // em uma única query, evitando o problema N+1.
    }

    public function listarPorCliente(int $clienteId): Collection
    {
        return Fatura::where('cliente_id', $clienteId)->get();
    }

    public function buscarPorId(int $id): Fatura
    {
        return Fatura::with('cliente')->findOrFail($id);
    }

    public function criar(array $dados): Fatura
    {
        return Fatura::create($dados);
    }

    public function deletar(Fatura $fatura): void
    {
        $fatura->delete();
    }
}
```

---

## 8. Ajustando os Controllers

Agora vamos atualizar os controllers para usar os services. Substitua o conteúdo dos controllers gerados pelo Blueprint:

```php
// app/Http/Controllers/ClienteController.php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    // Injeção de dependência via construtor.
    // O Laravel resolve automaticamente a instância do ClienteService.
    public function __construct(private ClienteService $service)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->listarTodos());
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        // $request->validated() retorna apenas os campos que passaram pela validação,
        // descartando qualquer campo extra que tenha vindo na requisição.
        $cliente = $this->service->criar($request->validated());
        return response()->json($cliente, 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        return response()->json($cliente);
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $atualizado = $this->service->atualizar($cliente, $request->validated());
        return response()->json($atualizado);
    }

    public function destroy(Cliente $cliente): JsonResponse
    {
        $this->service->deletar($cliente);
        return response()->json(null, 204);
    }
}
```

```php
// app/Http/Controllers/FaturaController.php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFaturaRequest;
use App\Models\Fatura;
use App\Services\FaturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    public function __construct(private FaturaService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        // Se vier o parâmetro ?cliente_id=1 na URL, filtra por cliente.
        // Caso contrário, retorna todas as faturas.
        if ($request->has('cliente_id')) {
            return response()->json(
                $this->service->listarPorCliente($request->integer('cliente_id'))
            );
        }

        return response()->json($this->service->listarTodas());
    }

    public function store(StoreFaturaRequest $request): JsonResponse
    {
        $fatura = $this->service->criar($request->validated());
        return response()->json($fatura->load('cliente'), 201);
        // load('cliente') carrega o relacionamento na resposta,
        // para que o JSON retorne os dados do cliente junto com a fatura criada.
    }

    public function show(Fatura $fatura): JsonResponse
    {
        return response()->json($fatura->load('cliente'));
    }

    public function destroy(Fatura $fatura): JsonResponse
    {
        $this->service->deletar($fatura);
        return response()->json(null, 204);
    }
}
```

---

## 9. Rodando as migrations e testando

### Rodando as migrations

```bash
php artisan migrate
```

Verifique as tabelas criadas:

```bash
psql -U postgres -d gestor_de_pagamentos -c "\dt"
```

Você deve ver `clientes` e `faturas` listadas junto com as tabelas padrão do Laravel.

### Populando com dados de teste (opcional)

O Blueprint gerou factories inteligentes para seus modelos. Para popular o banco com dados falsos durante o desenvolvimento:

```bash
php artisan tinker
```

Dentro do Tinker (REPL interativo do Laravel):

```php
// Cria 5 clientes com dados falsos
App\Models\Cliente::factory(5)->create();

// Cria 10 faturas vinculadas a clientes existentes
App\Models\Fatura::factory(10)->create();

exit
```

### Rodando os testes gerados

O Blueprint também gerou testes HTTP para os controllers. Para executá-los:

```bash
php artisan test
```

### Subindo o servidor

```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000/api`.

---

## 10. Tratamento de erros

### O comportamento padrão do Laravel

O Laravel 11 já trata erros de forma centralizada no arquivo `bootstrap/app.php`. Por padrão, quando uma rota de API lança uma exceção, o Laravel já retorna JSON com o status correto — diferente das rotas web, que retornam HTML.

Para personalizar as respostas de erro e garantir um formato consistente em toda a API, edite o `bootstrap/app.php`:

```php
// bootstrap/app.php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // Quando um Model não for encontrado (findOrFail falhou),
        // retorna 404 com uma mensagem estruturada.
        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            \Illuminate\Http\Request $request
        ): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Recurso não encontrado.',
                    'status'  => 404,
                ], 404);
            }
            return null;
        });

        // Quando a validação falha (FormRequest rejeitou a requisição),
        // retorna 422 com a lista de erros por campo.
        $exceptions->render(function (
            \Illuminate\Validation\ValidationException $e,
            \Illuminate\Http\Request $request
        ): ?JsonResponse {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Dados inválidos.',
                    'errors'  => $e->errors(),
                    'status'  => 422,
                ], 422);
            }
            return null;
        });

    })->create();
```

**Exemplos de resposta:**

Recurso não encontrado (`GET /api/clientes/999`):
```json
{
    "message": "Recurso não encontrado.",
    "status": 404
}
```

Validação falhou (`POST /api/clientes` sem o campo `email`):
```json
{
    "message": "Dados inválidos.",
    "status": 422,
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

## 11. Testando com Postman

Suba a aplicação:

```bash
php artisan serve
```

A API estará disponível em `http://localhost:8000/api`.

### Sequência sugerida para testar

Siga esta ordem — você precisa criar um cliente antes de criar uma fatura para ele.

**1. Criar um cliente**
```
POST http://localhost:8000/api/clientes
Content-Type: application/json

{
    "nome": "Maria Silva",
    "email": "maria@email.com",
    "telefone": "84999990000"
}
```
Resposta esperada: `201 Created`

**2. Listar todos os clientes**
```
GET http://localhost:8000/api/clientes
```

**3. Buscar cliente pelo ID**
```
GET http://localhost:8000/api/clientes/1
```

**4. Atualizar cliente**
```
PUT http://localhost:8000/api/clientes/1
Content-Type: application/json

{
    "nome": "Maria Souza",
    "email": "maria@email.com",
    "telefone": "84988880000"
}
```

**5. Criar uma fatura para o cliente**
```
POST http://localhost:8000/api/faturas
Content-Type: application/json

{
    "cliente_id": 1,
    "valor": 350.00,
    "vencimento": "2026-06-30",
    "status": "PENDENTE"
}
```
Resposta esperada: `201 Created` com o objeto da fatura e os dados do cliente embutidos.

**6. Listar faturas de um cliente específico**
```
GET http://localhost:8000/api/faturas?cliente_id=1
```

**7. Deletar uma fatura**
```
DELETE http://localhost:8000/api/faturas/1
```
Resposta esperada: `204 No Content` (sem corpo na resposta).

**8. Testar um erro de validação**
```
POST http://localhost:8000/api/clientes
Content-Type: application/json

{
    "nome": "Teste"
}
```
Resposta esperada: `422 Unprocessable Entity` com os erros de validação.

---

## 12. Próximos passos: autenticação com Sanctum

A API atual não possui nenhum controle de acesso. Qualquer pessoa com a URL pode criar, editar e deletar dados. O Laravel oferece o **Sanctum** para proteger os endpoints.

### O que é o Sanctum

Laravel Sanctum é o sistema de autenticação oficial do Laravel para APIs. Diferente do OAuth (que é mais complexo e voltado para integrações entre sistemas), o Sanctum usa tokens simples — ideal para APIs consumidas por um app mobile ou um frontend JavaScript.

O fluxo funciona assim:

1. O usuário envia email e senha para `POST /api/auth/login`
2. A API valida as credenciais e retorna um token
3. O cliente armazena o token e o envia em toda requisição subsequente no cabeçalho `Authorization: Bearer <token>`
4. A API valida o token a cada requisição via o middleware `auth:sanctum`

### Instalando o Sanctum

```bash
php artisan install:api
```

> Se você já rodou este comando na seção 3, o Sanctum já está instalado. Verifique se a tabela `personal_access_tokens` existe no banco.

### Próximos passos

1. Adicionar `HasApiTokens` no model `User`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

2. Criar um `AuthController` com os métodos `login` e `logout`

3. Criar as rotas públicas e protegidas em `routes/api.php`:

```php
// Rota pública — qualquer um pode acessar
Route::post('/auth/login', [AuthController::class, 'login']);

// Rotas protegidas — exigem token válido no cabeçalho
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('faturas', FaturaController::class);
});
```

4. No método `login`, validar as credenciais e retornar o token:

```php
public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Credenciais inválidas.'], 401);
    }

    $token = $request->user()->createToken('api-token')->plainTextToken;

    return response()->json(['token' => $token]);
}
```
