<?php
// DELETE /api/excluir.php?id=5
// Exclui um registro do historico pelo id.
// Usa DELETE (nunca GET) porque a chamada apaga um dado.

require_once __DIR__ . '/funcoes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header('Allow: DELETE');
    responder(405, ['erro' => 'Metodo nao permitido. Use DELETE.']);
}

// 1) Validacao no servidor: o id precisa ser um numero inteiro positivo
$entrada = $_GET['id'] ?? null;
if (!is_string($entrada) || !preg_match('/^[1-9][0-9]*$/', $entrada)) {
    responder(400, ['erro' => 'Parametro "id" invalido. Informe um numero inteiro positivo.']);
}
$id = (int) $entrada;

// 2) Exclui do banco com prepared statement
$con = conectar();
try {
    $stmt = $con->prepare('DELETE FROM cotacoes WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $excluidos = $stmt->affected_rows;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    responder(500, ['erro' => 'Falha ao excluir a cotacao do banco.']);
}

// 3) Se nenhuma linha foi apagada, o id nao existe
if ($excluidos === 0) {
    responder(404, ['erro' => "Cotacao com id $id nao encontrada."]);
}

responder(200, ['mensagem' => 'Cotacao excluida.', 'id' => $id]);
