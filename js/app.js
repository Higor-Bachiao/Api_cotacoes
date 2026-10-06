// A interface so conversa com o NOSSO servidor PHP.
// Ela nao chama a AwesomeAPI nem o banco diretamente.

const form = document.getElementById('form-busca');
const campo = document.getElementById('moeda');
const botao = document.getElementById('btn');
const resultado = document.getElementById('resultado');
const historico = document.getElementById('historico');

function mostrar(tipo, texto) {
  resultado.className = tipo; // "ok" ou "erro"
  resultado.textContent = texto;
}

// Busca os registros salvos e monta a tabela
async function carregarHistorico() {
  try {
    const resp = await fetch('api/listar.php');
    const dados = await resp.json();
    if (!resp.ok) throw new Error(dados.erro);

    historico.innerHTML = '';
    if (dados.cotacoes.length === 0) {
      historico.innerHTML = '<tr><td colspan="7">Nenhuma cotação salva ainda.</td></tr>';
      return;
    }
    for (const c of dados.cotacoes) {
      const tr = document.createElement('tr');
      const colunas = [c.id, c.par, c.compra, c.venda, c.variacao_pct + '%', c.data_cotacao, c.consultado_em];
      for (const valor of colunas) {
        const td = document.createElement('td');
        td.textContent = valor; // textContent evita injecao de HTML
        tr.appendChild(td);
      }
      historico.appendChild(tr);
    }
  } catch (e) {
    historico.innerHTML = '<tr><td colspan="7">Não foi possível carregar o histórico.</td></tr>';
  }
}

form.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  const moeda = campo.value.trim().toUpperCase();

  // Validacao no front (o servidor valida de novo)
  if (!/^[A-Z]{3,5}-[A-Z]{3,5}$/.test(moeda)) {
    mostrar('erro', 'Digite o par no formato USD-BRL.');
    return;
  }

  botao.disabled = true;
  try {
    const resp = await fetch('api/consultar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ moeda }),
    });
    const dados = await resp.json();

    if (!resp.ok) {
      mostrar('erro', dados.erro || 'Erro ao consultar a cotação.');
      return;
    }
    const c = dados.cotacao;
    mostrar('ok', `${c.nome}: compra ${c.compra} | venda ${c.venda} (cotação de ${c.data_cotacao})`);
    carregarHistorico();
  } catch (e) {
    mostrar('erro', 'Não foi possível falar com o servidor. Verifique se o Apache está ligado.');
  } finally {
    botao.disabled = false;
  }
});

carregarHistorico();
