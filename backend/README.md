# gestor-de-pagamentos — Guia: Construindo uma API REST com Java 21 e Spring Boot 4.0.6

Este guia acompanha a construção de uma API de gerenciamento de clientes e faturas do zero, cobrindo desde a criação do projeto até os endpoints funcionando com boas práticas de desenvolvimento.

---

## sumario

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

Antes de comecar, instale:

- [JDK 21](https://adoptium.net/)
- [Maven](https://maven.apache.org/install.html)
- [PostgreSQL 16](https://www.postgresql.org/download/) instalado localmente
- [IntelliJ IDEA](https://www.jetbrains.com/idea/) ou VS Code com extensao Java
- [Postman](https://www.postman.com/) para testar os endpoints

Verifique as instalacoes:

```bash
java -version   # deve mostrar 21.x.x
mvn -version
psql --version
```

---

## 2. Criando o projeto

Acesse [start.spring.io](https://start.spring.io) e configure:

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

Adicione as dependências:

- **Spring Web** — para criar os endpoints REST
- **Spring Data JPA** — para acessar o banco de dados
- **PostgreSQL Driver** — driver de conexao com o PostgreSQL
- **Lombok** — elimina codigo repetitivo como getters, setters e construtores
- **Spring Boot DevTools** — reinicia a aplicacao automaticamente ao salvar arquivos

Clique em **Generate**, extraia o zip e abra na sua IDE.

---

## 3. Configurando o banco de dados

Crie o banco no PostgreSQL:

```sql
CREATE DATABASE gestor_de_pagamentos;
```

Edite `src/main/resources/application.properties`:

```properties
spring.datasource.url=jdbc:postgresql://localhost:5432/gestor_de_pagamentos
spring.datasource.username=postgres
spring.datasource.password=sua_senha_aqui

spring.jpa.hibernate.ddl-auto=update
spring.jpa.show-sql=true
```

> Substitua `sua_senha_aqui` pela senha que voce definiu ao instalar o PostgreSQL.

> `ddl-auto=update` faz o Spring criar e atualizar as tabelas automaticamente com base nas entidades. Util durante o desenvolvimento.

### Rode e teste

Execute a aplicação para verificar se a conexao com o banco esta funcionando:

```bash
mvn spring-boot:run
```

Se tudo estiver certo, você vera no terminal uma linha como:

```
Started GestorDePagamentosApplication in 2.3 seconds
```

Se aparecer erro de conexao com o banco, verifique se o PostgreSQL esta rodando e se as credenciais no `application.properties` estao corretas. Encerre a aplicacao com `Ctrl+C` antes de continuar.

---

## 4. Estrutura de pacotes

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

Cada camada tem uma responsabilidade:

- **model** — define as tabelas do banco via anotacoes JPA
- **repository** — acessa o banco de dados
- **service** — contem as regras de negocio
- **controller** — recebe as requisicoes HTTP e retorna respostas

---

## 5. Entidades

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

> `@ManyToOne` indica que muitas faturas pertencem a um unico cliente. `@JoinColumn` define o nome da coluna de chave estrangeira no banco.

### Rode e teste

Execute a aplicacao novamente e confira se as tabelas foram criadas no banco:

```bash
mvn spring-boot:run
```

Com a aplicacao rodando, abra outro terminal e acesse o PostgreSQL para verificar:

```bash
psql -U postgres -d gestor_de_pagamentos -c "\dt"
```

Voce deve ver as tabelas `usuarios`, `clientes` e `faturas` listadas. Isso confirma que o JPA mapeou as entidades corretamente. Encerre com `Ctrl+C`.

---

## 6. Repositories

Repositories estendem `JpaRepository` e ganham metodos de CRUD prontos (save, findById, findAll, delete...) sem precisar escrever SQL.

```java
package com.example.gestor_de_pagamentos.repository;

import com.example.gestor_de_pagamentos.model.Cliente;
import org.springframework.data.jpa.repository.JpaRepository;

public interface ClienteRepository extends JpaRepository<Cliente, Long> {
}
```

```java
package com.example.gestor_de_pagamentos.repository;

import com.example.gestor_de_pagamentos.model.Fatura;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface FaturaRepository extends JpaRepository<Fatura, Long> {
    List<Fatura> findByClienteId(Long clienteId);
}
```

> O metodo `findByClienteId` e criado automaticamente pelo Spring Data a partir do nome. Nao e preciso escrever nenhuma query.

---

## 7. Services

A camada de servico contem a logica da aplicacao. Os controllers chamam os services; os services chamam os repositories.

```java
package com.example.gestor_de_pagamentos.service;

import com.example.gestor_de_pagamentos.model.Cliente;
import com.example.gestor_de_pagamentos.repository.ClienteRepository;
import org.springframework.stereotype.Service;
import java.util.List;

@Service
public class ClienteService {

    private final ClienteRepository repository;

    public ClienteService(ClienteRepository repository) {
        this.repository = repository;
    }

    public List<Cliente> listarTodos() {
        return repository.findAll();
    }

    public Cliente buscarPorId(Long id) {
        return repository.findById(id)
                .orElseThrow(() -> new RuntimeException("Cliente nao encontrado"));
    }

    public Cliente salvar(Cliente cliente) {
        return repository.save(cliente);
    }

    public Cliente atualizar(Long id, Cliente dados) {
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

Controllers recebem as requisicoes HTTP e delegam para os services.

### Boas praticas seguidas aqui

- **Versionamento via URL** — todos os endpoints ficam sob `/api/v1/`. Quando a API evoluir com mudancas incompativeis, uma nova versao `/api/v2/` pode ser criada sem quebrar quem ja usa a v1.
- **Uso correto dos verbos HTTP** — `GET` para leitura, `POST` para criacao, `PUT` para atualizacao completa, `DELETE` para remocao.
- **Codigos de status semanticos** — `200 OK`, `201 Created`, `204 No Content`, `404 Not Found`.
- **URI no recurso criado** — ao criar um recurso, o cabecalho `Location` retorna a URI do novo item.

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
@RequestMapping("/api/v1/clientes")
public class ClienteController {

    private final ClienteService service;

    public ClienteController(ClienteService service) {
        this.service = service;
    }

    @GetMapping
    public ResponseEntity<List<Cliente>> listar() {
        return ResponseEntity.ok(service.listarTodos());
    }

    @GetMapping("/{id}")
    public ResponseEntity<Cliente> buscar(@PathVariable Long id) {
        return ResponseEntity.ok(service.buscarPorId(id));
    }

    @PostMapping
    public ResponseEntity<Cliente> criar(@RequestBody Cliente cliente) {
        Cliente salvo = service.salvar(cliente);
        URI location = ServletUriComponentsBuilder
                .fromCurrentRequest()
                .path("/{id}")
                .buildAndExpand(salvo.getId())
                .toUri();
        return ResponseEntity.created(location).body(salvo);
    }

    @PutMapping("/{id}")
    public ResponseEntity<Cliente> atualizar(@PathVariable Long id, @RequestBody Cliente dados) {
        return ResponseEntity.ok(service.atualizar(id, dados));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> deletar(@PathVariable Long id) {
        service.deletar(id);
        return ResponseEntity.noContent().build();
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

Com controllers e services prontos, a API ja esta funcional. Suba a aplicacao:

```bash
mvn spring-boot:run
```

Abra o Postman e teste os endpoints basicos:

```
POST http://localhost:8080/api/v1/clientes
Content-Type: application/json

{
  "nome": "Maria Silva",
  "email": "maria@email.com",
  "telefone": "84999990000"
}
```

A resposta deve ser `201 Created` com o cliente criado no corpo. Em seguida:

```
GET http://localhost:8080/api/v1/clientes
```

Deve retornar a lista com o cliente que acabou de ser criado. Encerre com `Ctrl+C` antes de continuar.

---

## 9. Tratamento de erros

Quando um recurso não e encontrado, o Spring retorna um erro generico por padrao. Para retornar uma mensagem estruturada e com o status correto, crie um handler global:

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

Exemplo de resposta quando um cliente nao e encontrado:

```json
{
  "timestamp": "2026-05-21T10:30:00",
  "status": 404,
  "error": "Cliente nao encontrado"
}
```

### Rode e teste

suba a aplicação e tente buscar um cliente que nao existe:

```bash
mvn spring-boot:run
```

```
GET http://localhost:8080/api/v1/clientes/999
```

Agora a resposta deve ser `404 Not Found` com o JSON de erro estruturado, em vez de um erro generico do Spring.

---

## 10. Testando com Postman

Suba a aplicacao:

```bash
mvn spring-boot:run
```

A API estara disponivel em `http://localhost:8080/api/v1`.

### Exemplos de requisicoes

**Criar um cliente**
```
POST /api/v1/clientes
Content-Type: application/json

{
  "nome": "Maria Silva",
  "email": "maria@email.com",
  "telefone": "84999990000"
}
```

**Listar clientes**
```
GET /api/v1/clientes
```

**Buscar cliente por ID**
```
GET /api/v1/clientes/1
```

**Atualizar cliente**
```
PUT /api/v1/clientes/1
Content-Type: application/json

{
  "nome": "Maria Souza",
  "email": "maria@email.com",
  "telefone": "84988880000"
}
```

**Criar uma fatura para o cliente 1**
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

**Listar faturas de um cliente**
```
GET /api/v1/faturas/cliente/1
```

**Deletar fatura**
```
DELETE /api/v1/faturas/1
```

---

## 11. Proximos passos: autenticação

A API atual nao possui nenhum controle de acesso. Qualquer pessoa com a URL pode criar, editar e deletar dados. Em um sistema real, e necessario proteger os endpoints.

O ponto de entrada para isso seria a entidade `Usuario`, que ja foi criada com email e senha.

Os proximos passos seriam:

1. Adicionar a dependência **Spring Security** no `pom.xml`
2. Configurar autenticação com **JWT** (JSON Web Token)
3. Criar um endpoint `POST /api/v1/auth/login` que recebe email/senha e retorna um token
4. Exigir o token no cabecalho `Authorization: Bearer <token>` para acessar os demais endpoints
5. Definir quais rotas sao publicas e quais sao protegidas

