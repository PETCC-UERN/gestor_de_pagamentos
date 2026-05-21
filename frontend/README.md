# gestor-de-pagamentos — Guia: Construindo o Frontend com React, Vite e Tailwind CSS

Este guia acompanha a construção do frontend do gestor de pagamentos, consumindo a API REST construida no guia de backend. Ele foi escrito pensando em quem esta tendo o primeiro contato com desenvolvimento web frontend.

---

## O que vamos usar e por que

**React** e uma biblioteca JavaScript para construir interfaces de usuario. Em vez de manipular o HTML diretamente, voce descreve como a tela deve parecer e o React cuida de atualizar o que mudou. E a biblioteca de frontend mais usada no mercado.

**Vite** e a ferramenta que cria e roda o projeto durante o desenvolvimento. Ele e muito mais rapido que ferramentas mais antigas como o Create React App. Pense nele como o equivalente ao Maven do backend: gerencia dependencias e roda a aplicacao.

**Tailwind CSS** e um framework de CSS. Em vez de escrever arquivos `.css` separados, voce aplica classes diretamente nos elementos HTML para estilizar. Por exemplo, `className="text-blue-600 font-bold"` ja deixa um texto azul e negrito.

**Axios** e uma biblioteca para fazer requisicoes HTTP. E com ela que o frontend vai conversar com a API do backend, enviando e recebendo dados.

**React Router** e a biblioteca que cuida da navegacao entre paginas. Como o React nao tem esse recurso nativo, o React Router permite criar rotas como `/clientes` e `/faturas` dentro da mesma aplicacao.

---

## Sumario

1. [Pre-requisitos](#1-pre-requisitos)
2. [Criando o projeto](#2-criando-o-projeto)
3. [Instalando e configurando o Tailwind CSS](#3-instalando-e-configurando-o-tailwind-css)
4. [Estrutura de pastas](#4-estrutura-de-pastas)
5. [Configurando a conexao com a API](#5-configurando-a-conexao-com-a-api)
6. [Pagina de Clientes](#6-pagina-de-clientes)
7. [Pagina de Faturas](#7-pagina-de-faturas)
8. [Configurando as rotas](#8-configurando-as-rotas)
9. [Rode e teste](#9-rode-e-teste)
10. [Proximos passos: autenticacao](#10-proximos-passos-autenticacao)

---

## 1. Pre-requisitos

Antes de comecar, instale:

- [Node.js 20+](https://nodejs.org/) — o ambiente que executa JavaScript fora do navegador. O npm (gerenciador de pacotes) vem junto com ele, equivalente ao Maven no Java.
- A API do backend rodando em `http://localhost:8080`

Verifique a instalacao:

```bash
node -v   # deve mostrar v20.x.x ou superior
npm -v
```

---

## 2. Criando o projeto

O comando abaixo usa o Vite para gerar um projeto React ja configurado:

```bash
npm create vite@latest gestor-de-pagamentos-web -- --template react
cd gestor-de-pagamentos-web
npm install
```

- `npm create vite@latest` — baixa e executa o gerador de projetos do Vite
- `-- --template react` — diz ao Vite que queremos um projeto React
- `npm install` — baixa todas as dependencias listadas no `package.json` (equivalente ao `mvn install` do Java)

Apos isso, a estrutura inicial do projeto sera criada na pasta `gestor-de-pagamentos-web`.

---

## 3. Instalando e configurando o Tailwind CSS

```bash
npm install tailwindcss @tailwindcss/vite
```

Edite o arquivo `vite.config.js` para registrar o Tailwind como plugin do Vite:

```js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
})
```

Edite o arquivo `src/index.css` e substitua todo o conteudo por:

```css
@import "tailwindcss";
```

Essa linha importa todos os estilos base do Tailwind, deixando as classes disponiveis em toda a aplicacao.

### Rode e teste

```bash
npm run dev
```

Acesse `http://localhost:5173` no navegador. Se a pagina inicial do Vite aparecer, o projeto esta funcionando. Encerre com `Ctrl+C` antes de continuar.

---

## 4. Estrutura de pastas

Antes de comecar a codar, organize o projeto assim:

```
src/
├── api/
│   └── axios.js          # configuracao da conexao com o backend
├── pages/
│   ├── Clientes.jsx      # pagina completa de clientes
│   └── Faturas.jsx       # pagina completa de faturas
├── components/
│   ├── ClienteForm.jsx   # formulario de cadastro de cliente
│   ├── ClienteList.jsx   # listagem de clientes
│   ├── FaturaForm.jsx    # formulario de cadastro de fatura
│   └── FaturaList.jsx    # listagem de faturas
├── App.jsx               # componente raiz com as rotas
├── main.jsx              # ponto de entrada da aplicacao
└── index.css             # estilos globais
```

**Por que separar em `pages` e `components`?**

- `pages` contem a logica de cada tela: busca dados da API, gerencia o estado e passa as informacoes para os componentes.
- `components` contem apenas a parte visual: recebe dados via props e exibe na tela. Nao sabe nada sobre a API.

Essa separacao facilita a manutencao: se a API mudar, voce mexe apenas nas pages. Se o visual mudar, voce mexe apenas nos components.

---

## 5. Configurando a conexao com a API

Instale o Axios:

```bash
npm install axios
```

Crie o arquivo `src/api/axios.js`:

```js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8080/api/v1',
})

export default api
```

`axios.create` cria uma instancia configurada do Axios. Toda requisicao feita com essa instancia ja usa `http://localhost:8080/api/v1` como base, entao nos components voce so informa o restante da rota, como `/clientes` ou `/faturas`.

---

## 6. Pagina de Clientes

### O que e um componente?

No React, tudo e um componente: uma funcao JavaScript que retorna HTML. Componentes podem receber dados externos via **props** (abreviacao de properties), que funcionam como parametros de funcao.

### Componente de listagem

Crie `src/components/ClienteList.jsx`:

```jsx
export default function ClienteList({ clientes, onDeletar }) {
  return (
    <div className="mt-6">
      {clientes.length === 0 && (
        <p className="text-gray-500">Nenhum cliente cadastrado.</p>
      )}
      {clientes.map((cliente) => (
        <div
          key={cliente.id}
          className="flex justify-between items-center border rounded p-4 mb-2"
        >
          <div>
            <p className="font-semibold">{cliente.nome}</p>
            <p className="text-sm text-gray-500">{cliente.email}</p>
          </div>
          <button
            onClick={() => onDeletar(cliente.id)}
            className="text-red-500 hover:text-red-700 text-sm"
          >
            Remover
          </button>
        </div>
      ))}
    </div>
  )
}
```

- `{ clientes, onDeletar }` — props recebidas pela page
- `.map()` — percorre a lista de clientes e renderiza um card para cada um
- `key={cliente.id}` — o React exige uma chave unica em listas para identificar cada item

### Componente de formulario

Crie `src/components/ClienteForm.jsx`:

```jsx
import { useState } from 'react'

export default function ClienteForm({ onSalvar }) {
  const [form, setForm] = useState({ nome: '', email: '', telefone: '' })

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  function handleSubmit(e) {
    e.preventDefault()
    onSalvar(form)
    setForm({ nome: '', email: '', telefone: '' })
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-3">
      <input
        name="nome"
        value={form.nome}
        onChange={handleChange}
        placeholder="Nome"
        className="border rounded p-2"
        required
      />
      <input
        name="email"
        value={form.email}
        onChange={handleChange}
        placeholder="Email"
        type="email"
        className="border rounded p-2"
        required
      />
      <input
        name="telefone"
        value={form.telefone}
        onChange={handleChange}
        placeholder="Telefone"
        className="border rounded p-2"
      />
      <button
        type="submit"
        className="bg-blue-600 text-white rounded p-2 hover:bg-blue-700"
      >
        Salvar
      </button>
    </form>
  )
}
```

- `useState` — hook do React que cria uma variavel reativa. Quando o valor muda, o componente re-renderiza automaticamente.
- `handleChange` — atualiza o campo correspondente no estado a cada tecla digitada
- `e.preventDefault()` — impede o comportamento padrao do formulario HTML, que seria recarregar a pagina

### Pagina de Clientes

Crie `src/pages/Clientes.jsx`:

```jsx
import { useEffect, useState } from 'react'
import api from '../api/axios'
import ClienteForm from '../components/ClienteForm'
import ClienteList from '../components/ClienteList'

export default function Clientes() {
  const [clientes, setClientes] = useState([])

  async function carregar() {
    const response = await api.get('/clientes')
    setClientes(response.data)
  }

  async function salvar(dados) {
    await api.post('/clientes', dados)
    carregar()
  }

  async function deletar(id) {
    await api.delete(`/clientes/${id}`)
    carregar()
  }

  useEffect(() => {
    carregar()
  }, [])

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h1 className="text-2xl font-bold mb-4">Clientes</h1>
      <ClienteForm onSalvar={salvar} />
      <ClienteList clientes={clientes} onDeletar={deletar} />
    </div>
  )
}
```

- `useEffect(() => { ... }, [])` — executa a funcao uma vez quando o componente e carregado pela primeira vez. O array vazio `[]` significa "sem dependencias", ou seja, roda apenas na montagem.
- `async/await` — forma moderna de lidar com operacoes assincronas (como chamadas HTTP) sem travar a interface

---

## 7. Pagina de Faturas

### Componente de listagem

Crie `src/components/FaturaList.jsx`:

```jsx
export default function FaturaList({ faturas, onDeletar }) {
  return (
    <div className="mt-6">
      {faturas.length === 0 && (
        <p className="text-gray-500">Nenhuma fatura cadastrada.</p>
      )}
      {faturas.map((fatura) => (
        <div
          key={fatura.id}
          className="flex justify-between items-center border rounded p-4 mb-2"
        >
          <div>
            <p className="font-semibold">R$ {fatura.valor}</p>
            <p className="text-sm text-gray-500">
              Vencimento: {fatura.vencimento} — Status: {fatura.status}
            </p>
          </div>
          <button
            onClick={() => onDeletar(fatura.id)}
            className="text-red-500 hover:text-red-700 text-sm"
          >
            Remover
          </button>
        </div>
      ))}
    </div>
  )
}
```

### Componente de formulario

Crie `src/components/FaturaForm.jsx`:

```jsx
import { useState } from 'react'

export default function FaturaForm({ onSalvar }) {
  const [form, setForm] = useState({
    clienteId: '',
    valor: '',
    vencimento: '',
    status: 'PENDENTE',
  })

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  function handleSubmit(e) {
    e.preventDefault()
    onSalvar({
      cliente: { id: Number(form.clienteId) },
      valor: Number(form.valor),
      vencimento: form.vencimento,
      status: form.status,
    })
    setForm({ clienteId: '', valor: '', vencimento: '', status: 'PENDENTE' })
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-3">
      <input
        name="clienteId"
        value={form.clienteId}
        onChange={handleChange}
        placeholder="ID do Cliente"
        type="number"
        className="border rounded p-2"
        required
      />
      <input
        name="valor"
        value={form.valor}
        onChange={handleChange}
        placeholder="Valor"
        type="number"
        step="0.01"
        className="border rounded p-2"
        required
      />
      <input
        name="vencimento"
        value={form.vencimento}
        onChange={handleChange}
        type="date"
        className="border rounded p-2"
        required
      />
      <select
        name="status"
        value={form.status}
        onChange={handleChange}
        className="border rounded p-2"
      >
        <option value="PENDENTE">Pendente</option>
        <option value="PAGO">Pago</option>
        <option value="ATRASADO">Atrasado</option>
      </select>
      <button
        type="submit"
        className="bg-blue-600 text-white rounded p-2 hover:bg-blue-700"
      >
        Salvar
      </button>
    </form>
  )
}
```

### Pagina de Faturas

Crie `src/pages/Faturas.jsx`:

```jsx
import { useEffect, useState } from 'react'
import api from '../api/axios'
import FaturaForm from '../components/FaturaForm'
import FaturaList from '../components/FaturaList'

export default function Faturas() {
  const [faturas, setFaturas] = useState([])

  async function carregar() {
    const response = await api.get('/faturas')
    setFaturas(response.data)
  }

  async function salvar(dados) {
    await api.post('/faturas', dados)
    carregar()
  }

  async function deletar(id) {
    await api.delete(`/faturas/${id}`)
    carregar()
  }

  useEffect(() => {
    carregar()
  }, [])

  return (
    <div className="max-w-2xl mx-auto p-6">
      <h1 className="text-2xl font-bold mb-4">Faturas</h1>
      <FaturaForm onSalvar={salvar} />
      <FaturaList faturas={faturas} onDeletar={deletar} />
    </div>
  )
}
```

---

## 8. Configurando as rotas

Instale o React Router:

```bash
npm install react-router-dom
```

O React Router permite que a aplicacao tenha multiplas "paginas" sem recarregar o navegador. Cada rota mapeia uma URL para um componente.

Edite `src/App.jsx`:

```jsx
import { BrowserRouter, Routes, Route, Link } from 'react-router-dom'
import Clientes from './pages/Clientes'
import Faturas from './pages/Faturas'

export default function App() {
  return (
    <BrowserRouter>
      <nav className="bg-blue-600 text-white p-4 flex gap-6">
        <Link to="/clientes" className="hover:underline">Clientes</Link>
        <Link to="/faturas" className="hover:underline">Faturas</Link>
      </nav>
      <Routes>
        <Route path="/clientes" element={<Clientes />} />
        <Route path="/faturas" element={<Faturas />} />
        <Route path="/" element={<Clientes />} />
      </Routes>
    </BrowserRouter>
  )
}
```

- `BrowserRouter` — envolve toda a aplicacao e habilita o sistema de rotas
- `Routes` — agrupa as rotas definidas
- `Route` — mapeia um caminho (`path`) a um componente (`element`)
- `Link` — equivalente ao `<a>` do HTML, mas sem recarregar a pagina

---

## 9. Rode e teste

Certifique-se de que a API do backend esta rodando em `http://localhost:8080`. Em seguida, suba o frontend:

```bash
npm run dev
```

Acesse `http://localhost:5173` e teste:

- Cadastrar um cliente e ver na listagem
- Cadastrar uma fatura para esse cliente usando o ID retornado
- Remover um cliente ou fatura

### Erro de CORS

CORS (Cross-Origin Resource Sharing) e uma politica de seguranca do navegador que bloqueia requisicoes feitas de uma origem diferente da API. Como o frontend roda em `localhost:5173` e o backend em `localhost:8080`, o navegador bloqueia a comunicacao por padrao.

Para resolver, adicione a seguinte classe no projeto backend:

```java
package com.example.gestor_de_pagamentos;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

@Configuration
public class CorsConfig {

    @Bean
    public WebMvcConfigurer corsConfigurer() {
        return new WebMvcConfigurer() {
            @Override
            public void addCorsMappings(CorsRegistry registry) {
                registry.addMapping("/api/**")
                        .allowedOrigins("http://localhost:5173")
                        .allowedMethods("GET", "POST", "PUT", "DELETE");
            }
        };
    }
}
```

Reinicie o backend apos adicionar essa classe.

---

## 10. Proximos passos: autenticacao

Com a autenticacao implementada no backend, o frontend precisara:

1. Criar uma pagina de login com formulario de email e senha
2. Enviar as credenciais para `POST /api/v1/auth/login` e receber o token JWT
3. Armazenar o token no `localStorage`
4. Enviar o token em todas as requisicoes via cabecalho:

```js
api.defaults.headers.common['Authorization'] = `Bearer ${token}`
```

5. Redirecionar para o login caso a API retorne `401 Unauthorized`

