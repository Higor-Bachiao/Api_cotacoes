# Trabalho 1 — Consumindo APIs públicas

**Linguagem de Programação VIII — UNIFEG — Prof. José Bruno de Oliveira**
**Grupo 5:** Higor e Olimpio

Aplicação que consulta a cotação de um par de moedas na AwesomeAPI, grava o resultado
em um banco MySQL (com a data e a hora da consulta) e mostra o histórico salvo em uma
página simples.

## API escolhida

**AwesomeAPI** — cotação de moedas com histórico.
Documentação: https://docs.awesomeapi.com.br (não possui repositório oficial no GitHub).

Chamada usada:

```
GET https://economia.awesomeapi.com.br/json/last/USD-BRL
```

Resposta (200):

```json
{
  "USDBRL": {
    "code": "USD",
    "codein": "BRL",
    "name": "Dólar Americano/Real Brasileiro",
    "high": "5.222",
    "low": "4.9529",
    "varBid": "-0.2232",
    "pctChange": "-4.274227",
    "bid": "4.9988",
    "ask": "4.9993",
    "timestamp": "1791239406",
    "create_date": "2026-10-05 19:30:06"
  }
}
```

Para um par que não existe (ex.: `XXX-BRL`) a API responde **404** com
`{"status":404,"code":"CoinNotExists","message":"moeda nao encontrada XXX-BRL"}`.

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

O caminho de uma consulta:

1. O usuário digita o par (ex.: `USD-BRL`) e clica em **Consultar**.
2. O `js/app.js` valida o formato e envia `POST api/consultar.php` com `fetch()`.
3. O `consultar.php` valida de novo o parâmetro no servidor.
4. O PHP chama a AwesomeAPI com cURL e confere o status da resposta.
5. O PHP grava a cotação na tabela `cotacoes` com prepared statement.
6. O PHP devolve o registro gravado em JSON, com status **201**.
7. A página mostra o resultado e recarrega o histórico com `GET api/listar.php`.

Cada linha do histórico tem um botão **Excluir**, que envia `DELETE api/excluir.php?id=...`
com `fetch()` e recarrega a tabela.

A interface **nunca** acessa o banco nem a API pública: quem faz isso é o servidor.

**Por quê?**
- As credenciais do banco ficam só no servidor (`api/config.php`); no JavaScript qualquer um veria.
- O servidor valida os dados antes de gravar; o navegador pode ser manipulado pelo usuário.
- O servidor controla o que é salvo e padroniza os status e as mensagens de erro.
- Se a API pública mudar, só o PHP precisa ser ajustado.

## Estrutura

```
cotacoes/
├── index.html              interface (campo, botão, resultado, histórico com botão Excluir)
├── js/app.js               fetch() para o nosso servidor
├── api/
│   ├── config.php          credenciais do banco e URL da API
│   ├── funcoes.php         conexão, resposta JSON e validação
│   ├── consultar.php       POST: chama a API, grava e devolve JSON
│   ├── listar.php          GET: lista os registros salvos
│   └── excluir.php         DELETE: exclui um registro pelo id
├── sql/banco.sql           criação do banco e da tabela
└── postman/                coleção exportada
```

## Banco de dados

Banco `trabalho_cotacoes`, tabela `cotacoes`, criados pelo script `sql/banco.sql`.

| Coluna | Tipo | Origem |
|---|---|---|
| `id` | INT, chave primária | gerado pelo MySQL |
| `par` | VARCHAR(11) | par digitado, já validado (ex.: `USD-BRL`) |
| `nome` | VARCHAR(100) | `name` da API |
| `compra` | DECIMAL(18,6) | `bid` da API |
| `venda` | DECIMAL(18,6) | `ask` da API |
| `maxima` | DECIMAL(18,6) | `high` da API |
| `minima` | DECIMAL(18,6) | `low` da API |
| `variacao_pct` | DECIMAL(10,4) | `pctChange` da API |
| `data_cotacao` | DATETIME | `create_date` da API |
| `consultado_em` | DATETIME | data e hora da nossa consulta (preenchido pelo MySQL) |

## Endpoints do servidor

| Método | URL | Descrição | Status |
|---|---|---|---|
| POST | `/cotacoes/api/consultar.php` | Corpo `{"moeda":"USD-BRL"}`. Consulta a API, grava e devolve a cotação | 201, 400, 404, 405, 500, 502 |
| GET | `/cotacoes/api/listar.php` | Lista o histórico (aceita `?moeda=USD-BRL`) | 200, 400, 405, 500 |
| DELETE | `/cotacoes/api/excluir.php?id=1` | Exclui o registro com o id informado | 200, 400, 404, 405, 500 |

A consulta usa **POST** porque cada chamada cria um registro no histórico, e a
exclusão usa **DELETE** (nada é gravado, alterado ou excluído por link GET).

### Significado de cada status

| Status | Quando acontece |
|---|---|
| **200** | Listagem devolvida ou registro excluído |
| **201** | Cotação consultada e registro criado |
| **400** | Parâmetro `moeda` fora do formato `USD-BRL`, ou `id` que não é um inteiro positivo |
| **404** | A API não conhece o par, ou não existe registro com o `id` informado |
| **405** | Método errado (ex.: GET em `consultar.php` ou em `excluir.php`) |
| **500** | Falha ao conectar, gravar ou excluir no banco |
| **502** | A API externa não respondeu ou respondeu com erro |

### Exemplos

Consultar e salvar:

```
POST /cotacoes/api/consultar.php
Content-Type: application/json

{"moeda": "USD-BRL"}
```

```json
201 Created
{
  "mensagem": "Cotacao consultada e salva.",
  "cotacao": {
    "id": 1,
    "par": "USD-BRL",
    "nome": "Dólar Americano/Real Brasileiro",
    "compra": "4.998800",
    "venda": "4.999300",
    "maxima": "5.222000",
    "minima": "4.952900",
    "variacao_pct": "-4.2742",
    "data_cotacao": "2026-10-05 19:30:06",
    "consultado_em": "2026-10-05 19:42:17"
  }
}
```

Listar o histórico:

```
GET /cotacoes/api/listar.php
```

```json
200 OK
{
  "total": 1,
  "cotacoes": [ { "id": 1, "par": "USD-BRL", "...": "..." } ]
}
```

Excluir um registro:

```
DELETE /cotacoes/api/excluir.php?id=5
```

```json
200 OK
{"mensagem": "Cotacao excluida.", "id": 5}
```

Erros:

```json
400  {"erro": "Parametro \"moeda\" invalido. Use o formato USD-BRL."}
400  {"erro": "Parametro \"id\" invalido. Informe um numero inteiro positivo."}
404  {"erro": "Par de moedas \"XXX-BRL\" nao encontrado."}
404  {"erro": "Cotacao com id 5 nao encontrada."}
405  {"erro": "Metodo nao permitido. Use POST."}
502  {"erro": "A API externa respondeu com status 429."}
```

## Como rodar no XAMPP

1. Copie a pasta `cotacoes` para `C:\xampp\htdocs\`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. Abra `http://localhost/phpmyadmin`, vá em **Importar** e selecione `sql/banco.sql`.
4. Se o seu MySQL tiver senha, ajuste `api/config.php`.
5. Acesse `http://localhost/cotacoes/`.
6. Digite um par (ex.: `USD-BRL`, `EUR-BRL`, `BTC-BRL`) e clique em **Consultar**.

Requer a extensão cURL do PHP (já vem ativa no XAMPP).

### Se algo der errado

| Sintoma | Causa provável | O que fazer |
|---|---|---|
| "Falha ao conectar no banco de dados." (500) | MySQL desligado, banco não importado ou senha diferente | Iniciar o MySQL, importar `sql/banco.sql`, conferir `api/config.php` |
| 502 com `SSL certificate problem` no campo `detalhe` | O PHP não encontrou a lista de certificados para validar o HTTPS da API | Baixar o `cacert.pem` em https://curl.se/docs/caextract.html e apontar `curl.cainfo` para ele no `php.ini`; reiniciar o Apache |
| 502 "A API externa respondeu com status 429." | Limite de requisições da AwesomeAPI | Aguardar cerca de um minuto e tentar de novo |
| "Não foi possível falar com o servidor." na página | Apache desligado ou pasta fora de `htdocs` | Iniciar o Apache e conferir o endereço |

## Postman

Importe `postman/Trabalho1_Cotacoes.postman_collection.json`.

| # | Requisição | Método | Corpo | Status esperado |
|---|---|---|---|---|
| 1 | API pública direta | GET | — | 200 |
| 2 | Servidor: consultar e salvar | POST | `{"moeda":"USD-BRL"}` | 201 |
| 3 | Servidor: listar salvos | GET | — | 200 |
| 4 | Erro proposital: parâmetro inválido | POST | `{"moeda":"dolar"}` | 400 |
| 5 | Erro proposital: moeda inexistente | POST | `{"moeda":"XXX-BRL"}` | 404 |
| 6 | Servidor: listar filtrando por moeda | GET | — | 200 |
| 7 | Servidor: excluir registro (`?id=1`) | DELETE | — | 200 |
| 8 | Erro proposital: excluir id inexistente (`?id=999999`) | DELETE | — | 404 |

A requisição 4 é barrada pela validação do nosso servidor e nem chega à AwesomeAPI.
A requisição 5 depende da API: se ela estiver limitando as chamadas, o servidor
devolve 502 em vez de 404.

## Boas práticas aplicadas

- **Validação no servidor:** a função `validarPar()` (`api/funcoes.php`) confere o formato
  mesmo que o front já tenha validado.
- **Prepared statements:** o INSERT, os SELECT e o DELETE usam `prepare()` e `bind_param()`;
  nenhum valor vindo de fora é concatenado no SQL.
- **Credenciais só no servidor:** usuário e senha do banco ficam em `api/config.php`; o
  JavaScript não conhece o banco nem a API pública.
- **Nada é gravado nem excluído por GET:** a gravação só acontece por POST e a exclusão
  só por DELETE; GET em `consultar.php` ou em `excluir.php` devolve 405.
- **Saída segura na página:** o histórico é montado com `textContent`, que não interpreta HTML.

## Observações sobre a API

- Sem chave de API, a AwesomeAPI mantém as respostas em cache por cerca de 1 minuto.
  Por isso a tabela guarda dois horários: `data_cotacao` (informado pela API) e
  `consultado_em` (momento da nossa consulta), que é o que forma o histórico.
- Sem chave, a API também limita a quantidade de chamadas e passa a responder 429.
  O servidor trata isso como falha da API externa (502).
- Para usar uma chave (cadastro gratuito em https://awesomeapi.com.br), preencha `API_KEY`
  em `api/config.php`. O servidor envia a chave no cabeçalho `x-api-key`; ela nunca vai
  para o navegador. Com `API_KEY` vazio a aplicação funciona sem chave.
