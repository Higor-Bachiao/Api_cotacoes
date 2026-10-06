<?php
// GET /api/listar.php            -> lista todo o historico (mais recentes primeiro)
// GET /api/listar.php?moeda=USD-BRL -> filtra por par de moedas

require_once __DIR__ . '/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['erro' => 'Metodo nao permitido. Use GET.']);
}

$par = null;
if (isset($_GET['moeda']) && $_GET['moeda'] !== '') {
    $par = validarPar($_GET['moeda']);
    if ($par === null) {
        responder(400, ['erro' => 'Parametro "moeda" invalido. Use o formato USD-BRL.']);
    }
}

$con = conectar();
try {
    if ($par !== null) {
        $stmt = $con->prepare('SELECT * FROM cotacoes WHERE par = ? ORDER BY id DESC LIMIT 100');
        $stmt->bind_param('s', $par);
    } else {
        $stmt = $con->prepare('SELECT * FROM cotacoes ORDER BY id DESC LIMIT 100');
    }
    $stmt->execute();
    $registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    responder(500, ['erro' => 'Falha ao ler as cotacoes do banco.']);
}

responder(200, ['total' => count($registros), 'cotacoes' => $registros]);
