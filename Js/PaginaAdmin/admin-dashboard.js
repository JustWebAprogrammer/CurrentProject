// Variáveis globais
let mesasAtivas = [];

// Inicialização da página
document.addEventListener('DOMContentLoaded', function() {
    carregarEstatisticas();
    carregarMesas();
    verificarMensagens();
});

// Verificar mensagens na URL
function verificarMensagens() {
    const urlParams = new URLSearchParams(window.location.search);
    const erro = urlParams.get('erro');
    const sucesso = urlParams.get('sucesso');

    if (erro) {
        alert('Erro: ' + erro);
        // Limpar a URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if (sucesso) {
        alert('Sucesso: ' + sucesso);
        // Limpar a URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

// Carregar estatísticas do dashboard
async function carregarEstatisticas() {
    try {
        const response = await fetch('BackEnd/api/admin-dashboard.php?acao=estatisticas');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.erro) {
            console.error('Erro ao carregar estatísticas:', data.erro);
            // Mostrar valores padrão em caso de erro
            document.getElementById('total-clientes').textContent = '0';
            document.getElementById('reservas-hoje').textContent = '0';
            document.getElementById('mesas-ocupadas').textContent = '0';
            document.getElementById('taxa-ocupacao').textContent = '0%';
            return;
        }

        // Atualizar cards de estatísticas
        document.getElementById('total-clientes').textContent = data.totalClientes || 0;
        document.getElementById('reservas-hoje').textContent = data.reservasHoje || 0;
        document.getElementById('mesas-ocupadas').textContent = data.mesasOcupadas || 0;
        document.getElementById('taxa-ocupacao').textContent = (data.taxaOcupacao || 0) + '%';

    } catch (error) {
        console.error('Erro na requisição:', error);
        // Mostrar valores padrão em caso de erro
        document.getElementById('total-clientes').textContent = '0';
        document.getElementById('reservas-hoje').textContent = '0';
        document.getElementById('mesas-ocupadas').textContent = '0';
        document.getElementById('taxa-ocupacao').textContent = '0%';
    }
}

// Carregar lista de mesas
async function carregarMesas() {
    try {
        const response = await fetch('BackEnd/api/admin-dashboard.php?acao=mesas');
        const data = await response.json();
        
        if (data.erro) {
            console.error('Erro ao carregar mesas:', data.erro);
            return;
        }

        mesasAtivas = data;
        renderizarMesas(data);

    } catch (error) {
        console.error('Erro na requisição:', error);
    }
}

// Renderizar lista de mesas
function renderizarMesas(mesas) {
    const container = document.getElementById('mesas-lista');
    
    if (!mesas || mesas.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Nenhuma mesa cadastrada</p>';
        return;
    }

    let html = '';
    mesas.forEach(mesa => {
        const statusClass = mesa.estado === 'Livre' ? 'status-livre' : 'status-ocupada';
        
        html += `
            <div class="mesa-item">
                <div class="mesa-info">
                    <h4>Mesa ${mesa.id}</h4>
                    <p>Capacidade: ${mesa.capacidade} pessoas</p>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="mesa-status ${statusClass}">${mesa.estado}</span>
                    ${mesa.estado === 'Livre' ? 
                        `<button class="remover-btn" onclick="confirmarRemocaoMesa(${mesa.id})">Remover</button>` : 
                        '<span style="color: #666; font-size: 12px;">Mesa em uso</span>'
                    }
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Modal para adicionar mesa
function abrirModalMesa() {
    document.getElementById('modal-mesa').style.display = 'block';
}

function fecharModalMesa() {
    document.getElementById('modal-mesa').style.display = 'none';
    document.getElementById('form-mesa').reset();
}

// Adicionar nova mesa
async function adicionarMesa() {
    const capacidade = document.getElementById('capacidade-mesa').value;
    
    if (!capacidade) {
        alert('Por favor, selecione a capacidade da mesa.');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('acao', 'adicionar_mesa');
        formData.append('capacidade', capacidade);

        const response = await fetch('BackEnd/api/admin-dashboard.php', {
            method: 'POST',
            body: formData
        });

        // Como a resposta redireciona, vamos aguardar e recarregar
        setTimeout(() => {
            window.location.reload();
        }, 100);

    } catch (error) {
        console.error('Erro ao adicionar mesa:', error);
        alert('Erro ao adicionar mesa. Tente novamente.');
    }
}

// Confirmar remoção de mesa
function confirmarRemocaoMesa(mesaId) {
    if (confirm('Tem certeza que deseja remover esta mesa? Esta ação não pode ser desfeita.')) {
        removerMesa(mesaId);
    }
}

// Remover mesa
async function removerMesa(mesaId) {
    try {
        const formData = new FormData();
        formData.append('acao', 'remover_mesa');
        formData.append('mesa_id', mesaId);

        const response = await fetch('BackEnd/api/admin-dashboard.php', {
            method: 'POST',
            body: formData
        });

        // Como a resposta redireciona, vamos aguardar e recarregar
        setTimeout(() => {
            window.location.reload();
        }, 100);

    } catch (error) {
        console.error('Erro ao remover mesa:', error);
        alert('Erro ao remover mesa. Tente novamente.');
    }
}

// Gerar relatório diário
async function gerarRelatorio() {
    if (confirm('Deseja gerar o relatório diário? Isso atualizará as estatísticas do dia.')) {
        try {
            const formData = new FormData();
            formData.append('acao', 'gerar_relatorio');

            const response = await fetch('BackEnd/api/admin-dashboard.php', {
                method: 'POST',
                body: formData
            });

            // Como a resposta redireciona, vamos aguardar e recarregar
            setTimeout(() => {
                window.location.reload();
            }, 100);

        } catch (error) {
            console.error('Erro ao gerar relatório:', error);
            alert('Erro ao gerar relatório. Tente novamente.');
        }
    }
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modal-mesa');
    if (event.target === modal) {
        fecharModalMesa();
    }
}

// Atualizar dados periodicamente (a cada 30 segundos)
setInterval(() => {
    carregarEstatisticas();
    carregarMesas();
}, 30000);