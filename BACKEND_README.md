# gestor-de-pagamentos — Guia: Construindo uma API REST com Java 21 e Spring Boot 4.0.6

Este guia acompanha a construção de uma API de gerenciamento de clientes e faturas do zero. Ao final, você terá uma API funcional com endpoints para criar, listar, atualizar e deletar clientes e faturas, conectada a um banco de dados PostgreSQL.

O projeto segue uma arquitetura em camadas (controller → service → repository), que é o padrão mais usado no mercado com Spring Boot. Cada seção explica não só o que fazer, mas por que cada decisão foi tomada.

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
4. [Estrutura de pacotes](#4-estrutura-de-pacotes)
5. [Entidades](#5-entidades)
6. [Repositories](#6-repositories)
7. [Services](#7-services)
8. [Controllers](#8-controllers)
9. [Tratamento de erros](#9-tratamento-de-erros)
10. [Testando com Postman](#10-testando-com-postman)
11. [Proximos passos: autenticacao](#11-proximos-passos-autenticacao)

---

## 1. Pre-requisitos

### O que você vai precisar e por que

| Ferramenta | Para que serve |
|---|---|
| JDK 21 | O compilador e runtime do Java. Sem ele, nenhum código Java roda. |
| Maven | Gerenciador de dependências e build. Ele baixa as bibliotecas que o projeto precisa e empacota a aplicação. |
| PostgreSQL 16 | O banco de dados relacional onde os dados serão persistidos. |
| IntelliJ IDEA ou VS Code | A IDE onde você vai escrever o código. O IntelliJ tem suporte nativo a Java e Spring; o VS Code precisa da extensão Java. |
| Postman | Ferramenta visual para fazer requisições HTTP. Vai permitir testar os endpoints sem precisar de um frontend. |

### Instalação

- [JDK 21](https://adoptium.net/)
- [Maven](https://maven.apache.org/install.html)
- [PostgreSQL 16](https://www.postgresql.org/download/)
- [IntelliJ IDEA](https://www.jetbrains.com/idea/) ou VS Code com extensão Java
- [Postman](https://www.postman.com/)

Após instalar, verifique se tudo está funcionando:

```bash
java -version   # deve mostrar 21.x.x
mvn -version
psql --version
```

---

## 2. Criando o projeto

### O que é o Spring Initializr

O [start.spring.io](https://start.spring.io) é um gerador oficial de projetos Spring Boot. Em vez de criar manualmente toda a estrutura de pastas, arquivos de configuração e o `pom.xml`, você seleciona o que precisa e ele gera o esqueleto do projeto pronto para usar.

### Configuração

Acesse [start.spring.io](https://start.spring.io) e preencha:

| Campo | Valor |
|---|---|
| Project | Maven |
| Language | Java |
| Spring Boot | 4.0.6 |
| Group | com.example |
| Artifact | gestor-de-pagamentos |
| Package name | com.example.gestor_de_pagamentos |
| Java | 21 |
| Packaging | Jar |

### Dependências e por que cada uma importa

Clique em "Add Dependencies" e adicione:

**Spring Web**
Fornece tudo o que é necessário para criar endpoints HTTP. Inclui o Tomcat embutido (o servidor que fica escutando as requisições) e o suporte a anotações como `@RestController` e `@GetMapping`.

**Spring Data JPA**
Camada de abstração sobre o banco de dados. Com ela, você faz operações no banco usando objetos Java, sem escrever SQL na maioria dos casos. Por baixo dos panos, usa o Hibernate como implementação JPA.

**PostgreSQL Driver**
O driver JDBC que permite ao Java se comunicar com o PostgreSQL. Sem ele, a aplicação não consegue abrir conexão com o banco.

**Lombok**
Biblioteca que gera código repetitivo automaticamente em tempo de compilação. Com uma anotação como `@Getter`, você elimina a necessidade de escrever manualmente todos os métodos `get`. Isso deixa as classes de modelo muito mais limpas.

**Spring Boot DevTools**
Ferramenta de desenvolvimento que reinicia a aplicação automaticamente ao detectar mudanças nos arquivos. Economiza tempo durante o desenvolvimento.

Clique em **Generate**, extraia o zip e abra na sua IDE.

---

## 3. Configurando o banco de dados

### Criando o banco no PostgreSQL

Abra o terminal e acesse o PostgreSQL:

```bash
psql -U postgres
```

Crie o banco de dados:

```sql
CREATE DATABASE gestor_de_pagamentos;
```

### Conectando a aplicação ao banco

O arquivo `src/main/resources/application.properties` é onde ficam todas as configurações da aplicação. Edite-o com as informações de conexão:

```properties
spring.datasource.url=jdbc:postgresql://localhost:5432/gestor_de_pagamentos
spring.datasource.username=postgres
spring.datasource.password=sua_senha_aqui

spring.jpa.hibernate.ddl-auto=update
spring.jpa.show-sql=true
```

**O que cada configuração faz:**

- `spring.datasource.url` — endereço completo do banco, no formato `jdbc:driver://host:porta/nome_do_banco`
- `spring.datasource.username` e `password` — credenciais de acesso ao PostgreSQL
- `spring.jpa.hibernate.ddl-auto=update` — instrui o Hibernate a criar ou atualizar automaticamente as tabelas do banco com base nas entidades Java. Útil no desenvolvimento; em produção, prefira usar migrações com Flyway ou Liquibase
- `spring.jpa.show-sql=true` — exibe no terminal o SQL gerado pelo Hibernate, o que ajuda a entender o que está acontecendo no banco

> Substitua `sua_senha_aqui` pela senha que você definiu ao instalar o PostgreSQL.

### Rode e teste

Execute a aplicação para verificar se a conexão com o banco está funcionando:

```bash
mvn spring-boot:run
```

Se tudo estiver certo, você verá no terminal uma linha como:

```
Started GestorDePagamentosApplication in 2.3 seconds
```

Se aparecer erro de conexão, verifique se o PostgreSQL está rodando e se as credenciais no `application.properties` estão corretas. Encerre a aplicação com `Ctrl+C` antes de continuar.

---

## 4. Estrutura de pacotes

### A arquitetura em camadas

Este projeto segue uma arquitetura em camadas, que é o padrão mais comum em APIs Spring Boot. A ideia é separar as responsabilidades: cada camada só sabe o que precisa para fazer o seu trabalho, e se comunica apenas com a camada imediatamente abaixo dela.

```
Requisição HTTP
      |
  Controller       ← recebe a requisição e devolve a resposta
      |
   Service         ← contém as regras de negócio
      |
  Repository       ← acessa o banco de dados
      |
    Model          ← define a estrutura dos dados
```

Isso torna o código mais fácil de testar, de manter e de evoluir. Se você precisar trocar o banco de dados, só mexe nos repositories. Se uma regra de negócio mudar, só mexe nos services.

### Estrutura de pastas

Organize o projeto da seguinte forma:

```
src/main/java/com/example/gestor_de_pagamentos/
├── controller/
│   ├── ClienteController.java
│   └── FaturaController.java
├── model/
│   ├── Usuario.java
│   ├── Cliente.java
│   └── Fatura.java
├── repository/
│   ├── UsuarioRepository.java
│   ├── ClienteRepository.java
│   └── FaturaRepository.java
├── service/
│   ├── ClienteService.java
│   └── FaturaService.java
└── GestorDePagamentosApplication.java
```

---

## 5. Entidades

### O que são entidades JPA

Uma entidade é uma classe Java que representa uma tabela no banco de dados. Cada instância da classe corresponde a uma linha na tabela, e cada atributo corresponde a uma coluna. As anotações JPA (`@Entity`, `@Table`, `@Column` etc.) instruem o Hibernate sobre como fazer esse mapeamento.

### Anotações Lombok usadas nas entidades

Antes de ver o código, é útil entender o que o Lombok está gerando para você:

- `@Getter` — gera um método `get` para cada atributo (ex: `getNome()`)
- `@Setter` — gera um método `set` para cada atributo (ex: `setNome("valor")`)
- `@NoArgsConstructor` — gera um construtor sem parâmetros, necessário para o JPA
- `@AllArgsConstructor` — gera um construtor que recebe todos os atributos como parâmetro

### Anotações JPA usadas nas entidades

- `@Entity` — marca a classe como uma entidade gerenciada pelo JPA
- `@Table(name = "...")` — define o nome da tabela no banco
- `@Id` — marca o campo como chave primária
- `@GeneratedValue(strategy = GenerationType.IDENTITY)` — delega ao banco a geração automática do ID (usando `SERIAL` ou `IDENTITY` no PostgreSQL)
- `@Column(nullable = false)` — define que a coluna não pode ser nula no banco
- `@Column(unique = true)` — garante que o valor seja único na tabela

### Usuario

```java
package com.example.gestor_de_pagamentos.model;

import jakarta.persistence.*;
import lombok.*;

@Entity
@Table(name = "usuarios")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class Usuario {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String nome;

    @Column(nullable = false, unique = true)
    private String email;

    @Column(nullable = false)
    private String senha;
}
```

### Cliente

```java
package com.example.gestor_de_pagamentos.model;

import jakarta.persistence.*;
import lombok.*;

@Entity
@Table(name = "clientes")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class Cliente {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String nome;

    @Column(nullable = false, unique = true)
    private String email;

    private String telefone;
}
```

> Note que `telefone` não tem `@Column(nullable = false)`, o que significa que é um campo opcional.

### Fatura

```java
package com.example.gestor_de_pagamentos.model;

import jakarta.persistence.*;
import lombok.*;
import java.math.BigDecimal;
import java.time.LocalDate;

@Entity
@Table(name = "faturas")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
public class Fatura {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne
    @JoinColumn(name = "cliente_id", nullable = false)
    private Cliente cliente;

    @Column(nullable = false)
    private BigDecimal valor;

    @Column(nullable = false)
    private LocalDate vencimento;

    @Column(nullable = false)
    private String status; // "PENDENTE", "PAGO", "ATRASADO"
}
```

**Sobre o relacionamento entre Fatura e Cliente:**

- `@ManyToOne` — define um relacionamento de muitos para um. Muitas faturas podem pertencer ao mesmo cliente, mas cada fatura pertence a apenas um cliente.
- `@JoinColumn(name = "cliente_id")` — instrui o JPA a criar uma coluna chamada `cliente_id` na tabela `faturas`, que armazena o ID do cliente relacionado (chave estrangeira).
- `BigDecimal` é o tipo correto para valores monetários em Java. `double` e `float` têm problemas de precisão com decimais e não devem ser usados para dinheiro.
- `LocalDate` armazena apenas a data (sem hora), ideal para vencimentos.

### Rode e teste

Execute a aplicação e confirme que as tabelas foram criadas:

```bash
mvn spring-boot:run
```

Em outro terminal, acesse o PostgreSQL para verificar:

```bash
psql -U postgres -d gestor_de_pagamentos -c "\dt"
```

Você deve ver as tabelas `usuarios`, `clientes` e `faturas` listadas. Encerre com `Ctrl+C`.

---

## 6. Repositories

### O que é um Repository

A camada de repository é responsável exclusivamente por acessar o banco de dados. É aqui que ficam as queries — e, na maior parte do tempo, você não precisa escrevê-las.

Ao estender `JpaRepository<Entidade, TipoDoId>`, sua interface herda automaticamente uma série de métodos prontos:

| Método | O que faz |
|---|---|
| `save(entidade)` | Insere ou atualiza um registro |
| `findById(id)` | Busca por ID, retorna um `Optional` |
| `findAll()` | Retorna todos os registros |
| `deleteById(id)` | Remove pelo ID |
| `existsById(id)` | Verifica se um ID existe |

Além disso, o Spring Data consegue criar queries automaticamente a partir do nome do método. Por exemplo, um método chamado `findByClienteId(Long clienteId)` gera automaticamente a query `SELECT * FROM faturas WHERE cliente_id = ?`. Você define a assinatura; o Spring cuida do resto.

```java
package com.example.gestor_de_pagamentos.repository;

import com.example.gestor_de_pagamentos.model.Cliente;
import org.springframework.data.jpa.repository.JpaRepository;

public interface ClienteRepository extends JpaRepository<Cliente, Long> {
    // Os métodos de CRUD básicos já estão disponíveis via JpaRepository.
    // Adicione aqui métodos personalizados conforme a necessidade.
}
```

```java
package com.example.gestor_de_pagamentos.repository;

import com.example.gestor_de_pagamentos.model.Fatura;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface FaturaRepository extends JpaRepository<Fatura, Long> {
    // O Spring Data interpreta o nome do método e gera a query automaticamente:
    // SELECT * FROM faturas WHERE cliente_id = ?
    List<Fatura> findByClienteId(Long clienteId);
}
```

---

## 7. Services

### O papel da camada de service

Os services contêm a lógica da aplicação. É onde você responde perguntas como: "o que acontece quando eu tento atualizar um cliente que não existe?" ou "posso deletar uma fatura que já foi paga?".

Os controllers chamam os services; os services chamam os repositories. Os services não sabem nada sobre HTTP — eles só recebem dados, aplicam regras e retornam resultados.

A anotação `@Service` registra a classe como um componente gerenciado pelo Spring. Isso permite que ela seja injetada em outras classes via injeção de dependência (o construtor recebendo o repository, por exemplo).

```java
package com.example.gestor_de_pagamentos.service;

import com.example.gestor_de_pagamentos.model.Cliente;
import com.example.gestor_de_pagamentos.repository.ClienteRepository;
import org.springframework.stereotype.Service;
import java.util.List;

@Service
public class ClienteService {

    private final ClienteRepository repository;

    // Injeção de dependência via construtor.
    // O Spring cria uma instância do ClienteRepository e passa aqui automaticamente.
    // Essa abordagem é preferível ao @Autowired no campo, pois facilita testes.
    public ClienteService(ClienteRepository repository) {
        this.repository = repository;
    }

    public List<Cliente> listarTodos() {
        return repository.findAll();
    }

    public Cliente buscarPorId(Long id) {
        // findById retorna um Optional<Cliente>.
        // orElseThrow lança uma exceção se o Optional estiver vazio (registro não encontrado).
        return repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Cliente nao encontrado"));
    }

    public Cliente salvar(Cliente cliente) {
        return repository.save(cliente);
    }

    public Cliente atualizar(Long id, Cliente dados) {
        // Reutilizamos buscarPorId para garantir que o cliente existe antes de atualizar.
        // Se não existir, a exceção já é lançada aqui.
        Cliente cliente = buscarPorId(id);
        cliente.setNome(dados.getNome());
        cliente.setEmail(dados.getEmail());
        cliente.setTelefone(dados.getTelefone());
        return repository.save(cliente);
    }

    public void deletar(Long id) {
        repository.deleteById(id);
    }
}
```

```java
package com.example.gestor_de_pagamentos.service;

import com.example.gestor_de_pagamentos.model.Fatura;
import com.example.gestor_de_pagamentos.repository.FaturaRepository;
import org.springframework.stereotype.Service;
import java.util.List;

@Service
public class FaturaService {

    private final FaturaRepository repository;

    public FaturaService(FaturaRepository repository) {
        this.repository = repository;
    }

    public List<Fatura> listarTodas() {
        return repository.findAll();
    }

    public List<Fatura> listarPorCliente(Long clienteId) {
        return repository.findByClienteId(clienteId);
    }

    public Fatura buscarPorId(Long id) {
        return repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Fatura nao encontrada"));
    }

    public Fatura salvar(Fatura fatura) {
        return repository.save(fatura);
    }

    public void deletar(Long id) {
        repository.deleteById(id);
    }
}
```

---

## 8. Controllers

### O papel do controller

O controller é a porta de entrada da API. Ele recebe as requisições HTTP, extrai os dados necessários (parâmetros de URL, corpo da requisição) e delega o trabalho para o service. Depois, pega o resultado e devolve uma resposta HTTP adequada.

A anotação `@RestController` combina `@Controller` (registra a classe como controller) com `@ResponseBody` (serializa automaticamente os objetos retornados para JSON).

### Boas práticas aplicadas aqui

**Versionamento via URL (`/api/v1/`)**
Todos os endpoints ficam sob `/api/v1/`. Quando a API evoluir com mudanças incompatíveis, uma nova versão `/api/v2/` pode ser criada sem quebrar quem já usa a v1.

**Verbos HTTP semânticos**

| Verbo | Uso |
|---|---|
| `GET` | Leitura de dados (não modifica nada) |
| `POST` | Criação de um novo recurso |
| `PUT` | Atualização completa de um recurso existente |
| `DELETE` | Remoção de um recurso |

**Códigos de status HTTP corretos**

| Código | Significado | Quando usar |
|---|---|---|
| `200 OK` | Sucesso | Respostas a GET e PUT bem-sucedidos |
| `201 Created` | Recurso criado | Resposta a POST bem-sucedido |
| `204 No Content` | Sucesso sem corpo | Resposta a DELETE bem-sucedido |
| `404 Not Found` | Recurso não encontrado | Quando o ID solicitado não existe |

**Header `Location` no `201 Created`**
Ao criar um recurso, a resposta inclui o cabeçalho `Location` com a URL completa do novo recurso (ex: `/api/v1/clientes/5`). Isso segue o padrão REST e permite que o cliente saiba onde acessar o recurso recém-criado sem fazer uma segunda requisição.

```java
package com.example.gestor_de_pagamentos.controller;

import com.example.gestor_de_pagamentos.model.Cliente;
import com.example.gestor_de_pagamentos.service.ClienteService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.support.ServletUriComponentsBuilder;
import java.net.URI;
import java.util.List;

@RestController
@RequestMapping("/api/v1/clientes") // prefixo comum a todos os endpoints desta classe
public class ClienteController {

    private final ClienteService service;

    public ClienteController(ClienteService service) {
        this.service = service;
    }

    @GetMapping
    public ResponseEntity<List<Cliente>> listar() {
        return ResponseEntity.ok(service.listarTodos()); // 200 OK
    }

    @GetMapping("/{id}")
    public ResponseEntity<Cliente> buscar(@PathVariable Long id) {
        // @PathVariable extrai o {id} da URL e injeta no parâmetro do método
        return ResponseEntity.ok(service.buscarPorId(id));
    }

    @PostMapping
    public ResponseEntity<Cliente> criar(@RequestBody Cliente cliente) {
        // @RequestBody desserializa o JSON do corpo da requisição para um objeto Cliente
        Cliente salvo = service.salvar(cliente);
        URI location = ServletUriComponentsBuilder
                .fromCurrentRequest()      // pega a URL atual: /api/v1/clientes
                .path("/{id}")             // adiciona /{id}
                .buildAndExpand(salvo.getId()) // substitui {id} pelo ID gerado
                .toUri();
        return ResponseEntity.created(location).body(salvo); // 201 Created
    }

    @PutMapping("/{id}")
    public ResponseEntity<Cliente> atualizar(@PathVariable Long id, @RequestBody Cliente dados) {
        return ResponseEntity.ok(service.atualizar(id, dados));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> deletar(@PathVariable Long id) {
        service.deletar(id);
        return ResponseEntity.noContent().build(); // 204 No Content
    }
}
```

```java
package com.example.gestor_de_pagamentos.controller;

import com.example.gestor_de_pagamentos.model.Fatura;
import com.example.gestor_de_pagamentos.service.FaturaService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.support.ServletUriComponentsBuilder;
import java.net.URI;
import java.util.List;

@RestController
@RequestMapping("/api/v1/faturas")
public class FaturaController {

    private final FaturaService service;

    public FaturaController(FaturaService service) {
        this.service = service;
    }

    @GetMapping
    public ResponseEntity<List<Fatura>> listar() {
        return ResponseEntity.ok(service.listarTodas());
    }

    @GetMapping("/cliente/{clienteId}")
    public ResponseEntity<List<Fatura>> listarPorCliente(@PathVariable Long clienteId) {
        return ResponseEntity.ok(service.listarPorCliente(clienteId));
    }

    @GetMapping("/{id}")
    public ResponseEntity<Fatura> buscar(@PathVariable Long id) {
        return ResponseEntity.ok(service.buscarPorId(id));
    }

    @PostMapping
    public ResponseEntity<Fatura> criar(@RequestBody Fatura fatura) {
        Fatura salva = service.salvar(fatura);
        URI location = ServletUriComponentsBuilder
                .fromCurrentRequest()
                .path("/{id}")
                .buildAndExpand(salva.getId())
                .toUri();
        return ResponseEntity.created(location).body(salva);
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> deletar(@PathVariable Long id) {
        service.deletar(id);
        return ResponseEntity.noContent().build();
    }
}
```

### Rode e teste

Suba a aplicação:

```bash
mvn spring-boot:run
```

Abra o Postman e crie um cliente:

```
POST http://localhost:8080/api/v1/clientes
Content-Type: application/json

{
  "nome": "Maria Silva",
  "email": "maria@email.com",
  "telefone": "84999990000"
}
```

A resposta deve ser `201 Created` com o cliente no corpo e o header `Location` apontando para `/api/v1/clientes/1`. Em seguida:

```
GET http://localhost:8080/api/v1/clientes
```

Deve retornar a lista com o cliente criado. Encerre com `Ctrl+C` antes de continuar.

---

## 9. Tratamento de erros

### O problema do tratamento padrão

Quando um recurso não é encontrado, o Spring retorna por padrão um erro genérico com HTML ou um JSON pouco informativo. Isso dificulta o tratamento do erro no cliente (frontend, app mobile etc.).

### A solução: um handler global

`@RestControllerAdvice` permite criar uma classe que intercepta exceções lançadas em qualquer controller e define como elas devem ser traduzidas em respostas HTTP. Em vez de cada controller tratar seus próprios erros, você centraliza o tratamento em um único lugar.

```java
package com.example.gestor_de_pagamentos.controller;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;
import java.time.LocalDateTime;
import java.util.Map;

@RestControllerAdvice
public class GlobalExceptionHandler {

    // @ExceptionHandler define qual tipo de exceção este método vai interceptar.
    // Quando qualquer controller lançar uma RuntimeException, este método é chamado.
    @ExceptionHandler(RuntimeException.class)
    public ResponseEntity<Map<String, Object>> handleRuntimeException(RuntimeException ex) {
        Map<String, Object> body = Map.of(
                "timestamp", LocalDateTime.now(),
                "status", HttpStatus.NOT_FOUND.value(),
                "error", ex.getMessage()
        );
        return ResponseEntity.status(HttpStatus.NOT_FOUND).body(body);
    }
}
```

Agora, ao tentar buscar um cliente que não existe, a resposta será:

```json
{
  "timestamp": "2026-05-21T10:30:00",
  "status": 404,
  "error": "Cliente nao encontrado"
}
```

### Rode e teste

Suba a aplicação e tente buscar um ID inexistente:

```bash
mvn spring-boot:run
```

```
GET http://localhost:8080/api/v1/clientes/999
```

A resposta deve ser `404 Not Found` com o JSON de erro estruturado.

---

## 10. Testando com Postman

Suba a aplicação:

```bash
mvn spring-boot:run
```

A API estará disponível em `http://localhost:8080/api/v1`.

### Sequência sugerida para testar

Siga esta ordem para criar os dados com as dependências corretas (você precisa criar um cliente antes de criar uma fatura para ele).

**1. Criar um cliente**
```
POST /api/v1/clientes
Content-Type: application/json

{
  "nome": "Maria Silva",
  "email": "maria@email.com",
  "telefone": "84999990000"
}
```

**2. Listar todos os clientes**
```
GET /api/v1/clientes
```

**3. Buscar cliente pelo ID**
```
GET /api/v1/clientes/1
```

**4. Atualizar cliente**
```
PUT /api/v1/clientes/1
Content-Type: application/json

{
  "nome": "Maria Souza",
  "email": "maria@email.com",
  "telefone": "84988880000"
}
```

**5. Criar uma fatura para o cliente**

Note que o campo `cliente` recebe apenas o `id`. O Spring Data resolve o objeto completo a partir da chave estrangeira.

```
POST /api/v1/faturas
Content-Type: application/json

{
  "cliente": { "id": 1 },
  "valor": 350.00,
  "vencimento": "2026-06-30",
  "status": "PENDENTE"
}
```

**6. Listar faturas de um cliente**
```
GET /api/v1/faturas/cliente/1
```

**7. Deletar uma fatura**
```
DELETE /api/v1/faturas/1
```

---

## 11. Proximos passos: autenticação

A API atual não possui nenhum controle de acesso. Qualquer pessoa com a URL pode criar, editar e deletar dados. Em um sistema real, é necessário proteger os endpoints.

O ponto de entrada para isso é a entidade `Usuario`, que já foi criada com email e senha.

### O que é JWT

JWT (JSON Web Token) é um padrão para transmitir informações de autenticação de forma compacta e segura. O fluxo funciona assim:

1. O usuário envia email e senha para um endpoint de login
2. A API valida as credenciais e, se corretas, gera um token JWT assinado
3. O cliente armazena esse token e o envia no cabeçalho de todas as requisições subsequentes
4. A API valida o token a cada requisição e libera ou bloqueia o acesso

### Próximos passos

1. Adicionar a dependência **Spring Security** no `pom.xml`
2. Configurar autenticação com **JWT**
3. Criar um endpoint `POST /api/v1/auth/login` que recebe email/senha e retorna um token
4. Exigir o token no cabeçalho `Authorization: Bearer <token>` para acessar os demais endpoints
5. Definir quais rotas são públicas (o próprio endpoint de login) e quais são protegidas