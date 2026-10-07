<?php
// Credenciais do banco. Ficam SOMENTE no servidor, nunca no JavaScript.
// Valores padrao do XAMPP: usuario root, sem senha.
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'trabalho_cotacoes');

// Endereco base da API publica sorteada (AwesomeAPI).
define('API_URL', 'https://economia.awesomeapi.com.br/json/last/'); // nessa api tem o conteudo de todas as moedas, mas vamos usar apenas USD-BRL e EUR-BRL

// Chave da AwesomeAPI (opcional). Com ela o limite de requisicoes e maior.
// Fica SOMENTE no servidor. Deixe vazio para usar a API sem chave.
define('API_KEY', 'sk_jjihdvawOvxNiWXXKTCiwDZGKARqEHxfmnQ3Xxe5qNaoJn39R6qt5z9SVbHCs');
