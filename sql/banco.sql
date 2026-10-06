-- Trabalho 1 - Linguagem de Programacao VIII
-- Grupo 5: Higor e Olimpio - AwesomeAPI (cotacao de moedas com historico)
-- Importar pelo phpMyAdmin (aba Importar) ou colar na aba SQL.

CREATE DATABASE IF NOT EXISTS trabalho_cotacoes
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE trabalho_cotacoes;

CREATE TABLE IF NOT EXISTS cotacoes (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  par            VARCHAR(11)  NOT NULL,            -- ex.: USD-BRL
  nome           VARCHAR(100) NOT NULL,            -- ex.: Dolar Americano/Real Brasileiro
  compra         DECIMAL(18,6) NOT NULL,           -- bid
  venda          DECIMAL(18,6) NOT NULL,           -- ask
  maxima         DECIMAL(18,6) NOT NULL,           -- high
  minima         DECIMAL(18,6) NOT NULL,           -- low
  variacao_pct   DECIMAL(10,4) NOT NULL,           -- pctChange
  data_cotacao   DATETIME NOT NULL,                -- create_date informado pela API
  consultado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- data/hora da nossa consulta
  PRIMARY KEY (id),
  INDEX idx_par (par)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
