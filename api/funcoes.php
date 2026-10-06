<?php
require_once __DIR__ . '/config.php';

// Devolve um JSON com o status HTTP informado e encerra o script.
function responder(int $status, array $dados): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Abre a conexao com o MySQL. Se falhar, responde 500.
function conectar(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $con = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $con->set_charset('utf8mb4');
        return $con;
    } catch (mysqli_sql_exception $e) {
        responder(500, ['erro' => 'Falha ao conectar no banco de dados.']);
    }
}

// Valida o par de moedas. Aceita "USD-BRL" ou "usd-brl".
// Retorna o par em maiusculas ou null se o formato for invalido.
function validarPar($valor): ?string
{
    if (!is_string($valor)) {
        return null;
    }
    $par = strtoupper(trim($valor));
    if (!preg_match('/^[A-Z]{3,5}-[A-Z]{3,5}$/', $par)) {
        return null;
    }
    return $par;
}
