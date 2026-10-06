# Trabalho 1 — Consumindo APIs públicas

**Linguagem de Programação VIII — UNIFEG — Prof. José Bruno de Oliveira**
**Grupo 5:** Higor e Olimpio

## API escolhida

**AwesomeAPI** — cotação de moedas com histórico.
Documentação: https://docs.awesomeapi.com.br (não possui repositório oficial no GitHub).

Chamada usada: `GET https://economia.awesomeapi.com.br/json/last/USD-BRL`

A aplicação consulta a cotação de um par de moedas, grava o resultado na tabela
`cotacoes` (com a data/hora da consulta) e mostra o histórico salvo.

## Diagrama do fluxo

```
┌──────────────────┐  fetch()   ┌──────────────────┐   cURL    ┌──────────────────┐
│    Interface     │ ─────────► │   Servidor PHP   │ ────────► │    AwesomeAPI    │
│  HTML + JS       │ ◄───────── │   (Apache)       │ ◄──────── │  (API pública)   │
└──────────────────┘    JSON    └────────┬─────────┘   JSON    └──────────────────┘
                                         │ INSERT / SELECT
                                         ▼ (prepared statements)
                                ┌──────────────────┐
                                │   MySQL          │
                                │   tabela cotacoes│
                                └──────────────────┘
```

A interface **nunca** acessa o banco nem a API pública: quem faz isso é o servidor.

**Por quê?**
- As credenciais do banco ficam só no servidor (`api/config.php`); no JavaScript qualquer um veria.
- O servidor valida os dados antes de gravar; o navegador pode ser manipulado pelo usuário.
- O servidor controla o que é salvo e padroniza os status e as mensagens de erro.
- Se a API pública mudar, só o PHP precisa ser ajustado.

## Estrutura

```
cotacoes/
├── index.html              interface (campo, botão, resultado, histórico)
├── js/app.js               fetch() para o nosso servidor
├── api/
│   ├── config.php          credenciais do banco e URL da API
│   ├── funcoes.php         conexão, resposta JSON e validação
│   ├── consultar.php       POST: chama a API, grava e devolve JSON
│   └── listar.php          GET: lista os registros salvos
├── sql/banco.sql           criação do banco e da tabela
└── postman/                coleção exportada
```

## Endpoints do servidor

| Método | URL | Descrição | Status |
|---|---|---|---|
| POST | `/cotacoes/api/consultar.php` | Corpo `{"moeda":"USD-BRL"}`. Consulta a API, grava e devolve a cotação | 201, 400, 404, 500, 502 |
| GET | `/cotacoes/api/listar.php` | Lista o histórico (aceita `?moeda=USD-BRL`) | 200, 400, 500 |

Status usados: **201** registro criado, **200** listagem, **400** parâmetro inválido,
**404** moeda não encontrada, **405** método errado, **500** falha no banco,
**502** falha na API externa.

A consulta usa **POST** porque cada chamada cria um registro no histórico
(nada é gravado, alterado ou excluído por link GET).

## Como rodar no XAMPP

1. Copie a pasta `cotacoes` para `C:\xampp\htdocs\`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. Abra `http://localhost/phpmyadmin`, vá em **Importar** e selecione `sql/banco.sql`.
4. Se o seu MySQL tiver senha, ajuste `api/config.php`.
5. Acesse `http://localhost/cotacoes/`.
6. Digite um par (ex.: `USD-BRL`, `EUR-BRL`, `BTC-BRL`) e clique em **Consultar**.

Requer a extensão cURL do PHP (já vem ativa no XAMPP).

## Postman

Importe `postman/Trabalho1_Cotacoes.postman_collection.json`. Requisições:

1. API pública direta — GET — 200
2. Servidor: consultar e salvar — POST — 201
3. Servidor: listar salvos — GET — 200
4. Erro proposital: parâmetro inválido (`"dolar"`) — POST — 400
5. Erro proposital: moeda inexistente (`XXX-BRL`) — POST — 404
6. Servidor: listar filtrando por moeda — GET — 200

## Observação sobre a API

Sem chave de API, a AwesomeAPI mantém as respostas em cache por cerca de 1 minuto.
Por isso a tabela guarda dois horários: `data_cotacao` (informado pela API) e
`consultado_em` (momento da nossa consulta), que é o que forma o histórico.
