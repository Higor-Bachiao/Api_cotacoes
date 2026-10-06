<?php
// POST /api/consultar.php
// Corpo (JSON): { "moeda": "USD-BRL" }
// Fluxo: valida -> chama a AwesomeAPI (cURL) -> grava no MySQL -> devolve JSON.
// Usa POST porque cada chamada cria um registro novo no historico.

require_once __DIR__ . '/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, ['erro' => 'Metodo nao permitido. Use POST.']);
}

// 1) Le o parametro (JSON no corpo ou formulario comum)
$corpo = json_decode(file_get_contents('php://input'), true);
$entrada = $corpo['moeda'] ?? ($_POST['moeda'] ?? null);

// 2) Validacao no servidor
$par = validarPar($entrada);
if ($par === null) {
    responder(400, ['erro' => 'Parametro "moeda" invalido. Use o formato USD-BRL.']);
}

// 3) Chama a API publica com cURL
$ch = curl_init(API_URL . $par);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$resposta   = curl_exec($ch);
$statusApi  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroCurl   = curl_error($ch);

if ($resposta === false) {
    responder(502, ['erro' => 'Nao foi possivel acessar a API externa.', 'detalhe' => $erroCurl]);
}
if ($statusApi === 404) {
    responder(404, ['erro' => "Par de moedas \"$par\" nao encontrado."]);
}
if ($statusApi !== 200) {
    responder(502, ['erro' => "A API externa respondeu com status $statusApi."]);
}

// A resposta vem como { "USDBRL": { ...dados... } }
$json = json_decode($resposta, true);
$dados = is_array($json) ? reset($json) : null;
if (!is_array($dados) || !isset($dados['bid'], $dados['ask'], $dados['create_date'])) {
    responder(502, ['erro' => 'Resposta inesperada da API externa.']);
}

$nome     = (string) ($dados['name'] ?? $par);
$compra   = (string) $dados['bid'];
$venda    = (string) $dados['ask'];
$maxima   = (string) ($dados['high'] ?? $dados['bid']);
$minima   = (string) ($dados['low'] ?? $dados['bid']);
$variacao = (string) ($dados['pctChange'] ?? '0');
$dataCot  = (string) $dados['create_date'];

// 4) Grava no banco com prepared statement
$con = conectar();
try {
    $sql = 'INSERT INTO cotacoes
              (par, nome, compra, venda, maxima, minima, variacao_pct, data_cotacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
    $stmt = $con->prepare($sql);
    $stmt->bind_param('ssssssss', $par, $nome, $compra, $venda, $maxima, $minima, $variacao, $dataCot);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    // Busca o registro gravado para devolver tambem o consultado_em
    $stmt = $con->prepare('SELECT * FROM cotacoes WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    responder(500, ['erro' => 'Falha ao gravar a cotacao no banco.']);
}

// 5) 201 Created: um registro novo foi criado
responder(201, ['mensagem' => 'Cotacao consultada e salva.', 'cotacao' => $registro]);
