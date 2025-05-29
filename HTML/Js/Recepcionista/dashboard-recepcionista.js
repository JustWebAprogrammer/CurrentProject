// Variáveis globais para o dashboard
let dadosEstatisticas = {
    mesasDisponiveis: 0,
    mesasOcupadas: 0,
    reservasHoje: 0,
    proximasReservas: 0
};

let intervalosAtualizacao = [];

// Função para navegação
function navigateTo(page) {
    window.location.href = page;
}

// Função para scroll suave para seções específicas da página de recepção
function scrollToOcupacao() {
    // Redireciona para a página de recepção e foca na seção de ocupação
    window.location.href = 'Recepcionista.html#ocupacao';
}

function scrollToMesasOcupadas() {
    // Redireciona para a página de recepção e foca nas mesas ocupadas
    window.location.href = 'Recepcionista.html#mesas-ocupadas';
}

// Função para verificar autenticação
async function verificarAutenticacao() {
    try {
        const response = await fetch('BackEnd/auth_unified.php');
        const data = await response.text();
        
        // Se não estiver logado como admin ou recepcionista, redirecionar
        if (data.includes('não logado') || data.includes('acesso negado')) {
            window.location.href = 'Login.html';
            return false;
        }
        return true;
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        return false;
    }
}

// Função para obter informações do usuário logado
async function carregarInformacoesUsuario() {
    try {
        // Como não temos endpoint específico, vamos usar uma abordagem simples
        const nomeElement = document.getElementById('usuario-nome');
        if (nomeElement) {
            nomeElement.textContent = 'Bem-vindo(a), Recepcionista!';
        }
    } catch (error) {
        console.error('Erro ao carregar informações do usuário:', error);
    }
}

// Função para carregar estatísticas das mesas
async function carregarEstatisticasMesas() {
    try {
        // Carregar mesas disponíveis
        const responseDisponiveis = await fetch('BackEnd/api/mesas.php?acao=disponiveis');
        const dataDisponiveis = await responseDisponiveis.json();
        
        // Carregar mesas ocupadas
        const responseOcupadas = await fetch('BackEnd/api/mesas.php?acao=ocupadas');
        const dataOcupadas = await responseOcupadas.json();
        
        if (dataDisponiveis.sucesso && dataOcupadas.sucesso) {
            dadosEstatisticas.mesasDisponiveis = dataDisponiveis.mesas ? dataDisponiveis.mesas.length : 0;
            dadosEstatisticas.mesasOcupadas = dataOcupadas.mesas ? dataOcupadas.mesas.length : 0;
            
            atualizarExibicaoEstatisticas();
            atualizarMesasOverview(dataDisponiveis.mesas, dataOcupadas.mesas);
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas das mesas:', error);
        mostrarMensagem('Erro ao carregar dados das mesas', 'error');
    }
}

// Função para carregar estatísticas de reservas
async function carregarEstatisticasReservas() {
    try {
        const hoje = new Date().toISOString().split('T')[0];
        const response = await fetch(`BackEnd/api/reservas.php?data=${hoje}`);
        const data = await response.json();
        
        if (data.sucesso) {
            dadosEstatisticas.reservasHoje = data.reservas ? data.reservas.length : 0;
            
            // Calcular próximas reservas (próximas 2 horas)
            const agora = new Date();
            const proximasDuasHoras = new Date(agora.getTime() + (2 * 60 * 60 * 1000));
            
            dadosEstatisticas.proximasReservas = data.reservas ? data.reservas.filter(reserva => {
                const dataReserva = new Date(`${reserva.data} ${reserva.hora}`);
                return dataReserva >= agora && dataReserva <= proximasDuasHoras;
            }).length : 0;
            
            atualizarExibicaoEstatisticas();
            mostrarReservasHoje(data.reservas);
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas de reservas:', error);
        mostrarMensagem('Erro ao carregar dados de reservas', 'error');
    }
}

// Função para atualizar a exibição das estatísticas
function atualizarExibicaoEstatisticas() {
    document.getElementById('mesas-disponiveis').textContent = dadosEstatisticas.mesasDisponiveis;
    document.getElementById('mesas-ocupadas').textContent = dadosEstatisticas.mesasOcupadas;
    document.getElementById('reservas-hoje').textContent = dadosEstatisticas.reservasHoje;
    document.getElementById('proximas-reservas').textContent = dadosEstatisticas.proximasReservas;
}

// Função para mostrar reservas de hoje
function mostrarReservasHoje(reservas) {
    const listaElement = document.getElementById('reservas-hoje-lista');
    
    if (!reservas || reservas.length === 0) {
        listaElement.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Nenhuma reserva para hoje.</p>';
        return;
    }
    
    // Ordenar por horário
    reservas.sort((a, b) => a.hora.localeCompare(b.hora));
    
    listaElement.innerHTML = reservas.map(reserva => `
        <div class="reserva-card">
            <div class="reserva-info">
                <h4>Reserva #${reserva.id}</h4>
                <p><strong>Cliente:</strong> ${reserva.cliente_nome || 'N/A'}</p>
                <p><strong>Horário:</strong> ${reserva.hora}</p>
                <p><strong>Mesa:</strong> ${reserva.mesa_id} - <strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
            </div>
            <div class="reserva-status">
                <span class="status-badge ${getStatusClass(reserva.status)}">${reserva.status}</span>
            </div>
        </div>
    `).join('');
}

// Função para atualizar overview das mesas
function atualizarMesasOverview(mesasDisponiveis, mesasOcupadas) {
    const overviewElement = document.getElementById('mesas-overview');
    
    // Combinar todas as mesas
    const todasMesas = [];
    
    if (mesasDisponiveis) {
        mesasDisponiveis.forEach(mesa => {
            todasMesas.push({
                ...mesa,
                status: 'disponivel',
                statusIcon: '✅'
            });
        });
    }
    
    if (mesasOcupadas) {
        mesasOcupadas.forEach(mesa => {
            todasMesas.push({
                ...mesa,
                status: 'ocupada',
                statusIcon: '🔴'
            });
        });
    }
    
    // Ordenar por ID
    todasMesas.sort((a, b) => a.id - b.id);
    
    if (todasMesas.length === 0) {
        overviewElement.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Nenhuma mesa encontrada.</p>';
        return;
    }
    
    overviewElement.innerHTML = todasMesas.map(mesa => `
        <div class="mesa-overview-card ${mesa.status}">
            <div class="status-icon">${mesa.statusIcon}</div>
            <h4>Mesa ${mesa.id}</h4>
            <p>Cap: ${mesa.capacidade}</p>
            <p>${mesa.status === 'ocupada' ? `${mesa.num_pessoas || 'N/A'} pessoas` : 'Disponível'}</p>
            ${mesa.cliente_nome ? `<p><small>${mesa.cliente_nome}</small></p>` : ''}
        </div>
    `).join('');
}

// Função para obter classe CSS do status
function getStatusClass(status) {
    switch(status) {
        case 'Reservado': return 'status-reservado';
        case 'Confirmado': return 'status-confirmado';
        case 'Cancelado': return 'status-cancelado';
        default: return 'status-reservado';
    }
}

// Função para mostrar mensagens de status
function mostrarMensagem(mensagem, tipo = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `status-message ${tipo}`;
    messageDiv.textContent = mensagem;
    
    document.body.appendChild(messageDiv);
    
    // Mostrar mensagem
    setTimeout(() => {
        messageDiv.classList.add('show');
    }, 100);
    
    // Remover mensagem após 3 segundos
    setTimeout(() => {
        messageDiv.classList.remove('show');
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 3000);
}

// Função para atualizar dados manualmente
async function atualizarDados() {
    const botaoAtualizar = document.querySelector('.action-card:last-child');
    if (botaoAtualizar) {
        botaoAtualizar.classList.add('loading');
    }
    
    try {
        await Promise.all([
            carregarEstatisticasMesas(),
            carregarEstatisticasReservas()
        ]);
        
        mostrarMensagem('Dados atualizados com sucesso!', 'success');
    } catch (error) {
        console.error('Erro ao atualizar dados:', error);
        mostrarMensagem('Erro ao atualizar dados', 'error');
    } finally {
        if (botaoAtualizar) {
            botaoAtualizar.classList.remove('loading');
        }
    }
}

// Função para inicializar atualizações automáticas
function iniciarAtualizacaoAutomatica() {
    // Atualizar dados a cada 30 segundos
    const intervalo = setInterval(async () => {
        try {
            await Promise.all([
                carregarEstatisticasMesas(),
                carregarEstatisticasReservas()
            ]);
        } catch (error) {
            console.error('Erro na atualização automática:', error);
        }
    }, 30000);
    
    intervalosAtualizacao.push(intervalo);
}

// Função para parar atualizações automáticas
function pararAtualizacaoAutomatica() {
    intervalosAtualizacao.forEach(intervalo => {
        clearInterval(intervalo);
    });
    intervalosAtualizacao = [];
}

// Função para definir data atual
function definirDataAtual() {
    const agora = new Date();
    const opcoes = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    const dataFormatada = agora.toLocaleDateString('pt-BR', opcoes);
    const horaFormatada = agora.toLocaleTimeString('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    document.getElementById('data-atual').textContent = `${dataFormatada} - ${horaFormatada}`;
}

// Função para inicialização completa
async function inicializarDashboard() {
    try {
        // Verificar autenticação
        const autenticado = await verificarAutenticacao();
        if (!autenticado) return;
        
        // Definir data atual
        definirDataAtual();
        
        // Carregar informações do usuário
        await carregarInformacoesUsuario();
        
        // Carregar dados iniciais
        await Promise.all([
            carregarEstatisticasMesas(),
            carregarEstatisticasReservas()
        ]);
        
        // Iniciar atualizações automáticas
        iniciarAtualizacaoAutomatica();
        
        // Atualizar relógio a cada minuto
        setInterval(definirDataAtual, 60000);
        
        console.log('Dashboard do recepcionista carregado com sucesso!');
        
    } catch (error) {
        console.error('Erro ao inicializar dashboard:', error);
        mostrarMensagem('Erro ao carregar dashboard', 'error');
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', inicializarDashboard);

// Limpar intervalos quando a página for fechada
window.addEventListener('beforeunload', pararAtualizacaoAutomatica);

// Função para debug (pode ser removida em produção)
function debugDashboard() {
    console.log('Estado atual do dashboard:');
    console.log('Estatísticas:', dadosEstatisticas);
    console.log('Intervalos ativos:', intervalosAtualizacao.length);
}

// Função para verificar e expirar reservas automaticamente
async function verificarReservasExpiradas() {
    try {
        const response = await fetch('BackEnd/api/gerenciar-status-reservas.php?acao=expirar_automatico');
        const data = await response.json();
        
        if (data.sucesso && data.reservas_expiradas > 0) {
            console.log(`${data.reservas_expiradas} reservas foram expiradas automaticamente`);
            // Recarregar dados após expiração
            await carregarEstatisticasReservas();
        }
    } catch (error) {
        console.error('Erro ao verificar reservas expiradas:', error);
    }
}

// Função para carregar reservas pendentes (que podem expirar)
async function carregarReservasPendentes() {
    try {
        const response = await fetch('BackEnd/api/gerenciar-status-reservas.php?acao=reservas_pendentes');
        const data = await response.json();
        
        if (data.sucesso) {
            mostrarReservasPendentes(data.reservas);
        }
    } catch (error) {
        console.error('Erro ao carregar reservas pendentes:', error);
    }
}

// Função para mostrar reservas que precisam de atenção
function mostrarReservasPendentes(reservas) {
    const container = document.getElementById('reservas-pendentes-lista');
    if (!container) return;
    
    if (!reservas || reservas.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666;">Nenhuma reserva pendente.</p>';
        return;
    }
    
    container.innerHTML = reservas.map(reserva => {
        const minutosAtraso = reserva.minutos_atraso;
        const statusCor = minutosAtraso > 20 ? '#ff4444' : (minutosAtraso > 10 ? '#ff8800' : '#ffaa00');
        
        return `
            <div class="reserva-pendente-card" style="border-left: 4px solid ${statusCor}">
                <div class="reserva-info">
                    <h4>Reserva #${reserva.id} - ${reserva.cliente_nome}</h4>
                    <p><strong>Horário:</strong> ${reserva.hora}</p>
                    <p><strong>Mesa:</strong> ${reserva.mesa_id} - <strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
                    <p><strong>Atraso:</strong> ${minutosAtraso} minutos</p>
                    <p><strong>Contato:</strong> ${reserva.telemovel}</p>
                </div>
                <div class="reserva-acoes">
                    <button class="btn-concluir" onclick="alterarStatusReserva(${reserva.id}, 'Concluido')">
                        ✅ Concluir
                    </button>
                    <button class="btn-expirar" onclick="alterarStatusReserva(${reserva.id}, 'Expirado')">
                        ⏰ Expirar
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Função para alterar status da reserva
async function alterarStatusReserva(reservaId, novoStatus) {
    if (!confirm(`Tem certeza que deseja marcar esta reserva como ${novoStatus}?`)) {
        return;
    }
    
    try {
        const response = await fetch('BackEnd/api/gerenciar-status-reservas.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                reserva_id: reservaId,
                novo_status: novoStatus
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            mostrarMensagem(data.mensagem, 'success');
            // Recarregar dados
            await Promise.all([
                carregarEstatisticasMesas(),
                carregarEstatisticasReservas(),
                carregarReservasPendentes()
            ]);
        } else {
            mostrarMensagem(data.erro || 'Erro ao alterar status', 'error');
        }
    } catch (error) {
        console.error('Erro ao alterar status da reserva:', error);
        mostrarMensagem('Erro interno do servidor', 'error');
    }
}

// Modificar a função de inicialização para incluir verificação de expiração
async function inicializarDashboard() {
    try {
        // Verificar autenticação
        const autenticado = await verificarAutenticacao();
        if (!autenticado) return;
        
        // Definir data atual
        definirDataAtual();
        
        // Carregar informações do usuário
        await carregarInformacoesUsuario();
        
        // Verificar reservas expiradas primeiro
        await verificarReservasExpiradas();
        
        // Carregar dados iniciais
        await Promise.all([
            carregarEstatisticasMesas(),
            carregarEstatisticasReservas(),
            carregarReservasPendentes()
        ]);
        
        // Iniciar atualizações automáticas (incluindo verificação de expiração)
        iniciarAtualizacaoAutomatica();
        
        // Atualizar relógio a cada minuto
        setInterval(definirDataAtual, 60000);
        
        console.log('Dashboard do recepcionista carregado com sucesso!');
        
    } catch (error) {
        console.error('Erro ao inicializar dashboard:', error);
        mostrarMensagem('Erro ao carregar dashboard', 'error');
    }
}

// Modificar a função de atualização automática
function iniciarAtualizacaoAutomatica() {
    // Atualizar dados a cada 30 segundos
    const intervalo = setInterval(async () => {
        try {
            // Verificar expiração a cada atualização
            await verificarReservasExpiradas();
            
            await Promise.all([
                carregarEstatisticasMesas(),
                carregarEstatisticasReservas(),
                carregarReservasPendentes()
            ]);
        } catch (error) {
            console.error('Erro na atualização automática:', error);
        }
    }, 30000);
    
    intervalosAtualizacao.push(intervalo);
}

// Função para carregar reservas que precisam de atenção do garçom
async function carregarReservasParaGarcom() {
    try {
        const response = await fetch('BackEnd/api/gerenciar-status-reservas.php?acao=reservas_para_garcom');
        const data = await response.json();
        
        if (data.sucesso) {
            mostrarReservasParaGarcom(data.reservas);
        }
    } catch (error) {
        console.error('Erro ao carregar reservas para garçom:', error);
    }
}

// Função para mostrar reservas que o garçom pode gerenciar
function mostrarReservasParaGarcom(reservas) {
    const container = document.getElementById('reservas-garcom-lista');
    if (!container) return;
    
    if (!reservas || reservas.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666;">Nenhuma reserva ativa no momento.</p>';
        return;
    }
    
    container.innerHTML = reservas.map(reserva => {
        const agora = new Date();
        const horaReserva = new Date(`${reserva.data} ${reserva.hora}`);
        const diferenca = Math.floor((agora - horaReserva) / (1000 * 60)); // diferença em minutos
        
        let statusCor = '#4CAF50'; // Verde para reservas normais
        let statusTexto = 'No horário';
        
        if (diferenca > 0) {
            statusCor = diferenca > 15 ? '#f44336' : '#ff9800'; // Vermelho se > 15min, laranja se > 0
            statusTexto = diferenca > 15 ? `${diferenca} min atrasado` : `${diferenca} min de atraso`;
        } else if (diferenca > -15) {
            statusCor = '#2196F3'; // Azul para reservas próximas (15 min antes)
            statusTexto = 'Cliente pode chegar a qualquer momento';
        }
        
        return `
            <div class="reserva-garcom-card" style="border-left: 4px solid ${statusCor}">
                <div class="reserva-header">
                    <h4>Reserva #${reserva.id}</h4>
                    <span class="status-badge ${getStatusClass(reserva.status)}">${reserva.status}</span>
                </div>
                <div class="reserva-info">
                    <p><strong>Cliente:</strong> ${reserva.cliente_nome}</p>
                    <p><strong>Horário:</strong> ${reserva.hora}</p>
                    <p><strong>Mesa:</strong> ${reserva.mesa_id || 'A ser definida'} - <strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
                    <p><strong>Status:</strong> <span style="color: ${statusCor}; font-weight: bold;">${statusTexto}</span></p>
                    <p><strong>Contato:</strong> ${reserva.telemovel}</p>
                </div>
                <div class="reserva-acoes-garcom">
                    ${getAcoesGarcom(reserva)}
                </div>
            </div>
        `;
    }).join('');
}

// Função para definir quais ações o garçom pode fazer baseado no status atual
function getAcoesGarcom(reserva) {
    const acoes = [];
    
    switch(reserva.status) {
        case 'Reservado':
            acoes.push(`
                <button class="btn-confirmar" onclick="alterarStatusReservaGarcom(${reserva.id}, 'Confirmado')">
                    ✅ Cliente Chegou
                </button>
            `);
            acoes.push(`
                <button class="btn-expirar" onclick="alterarStatusReservaGarcom(${reserva.id}, 'Expirado')">
                    ❌ Cliente Não Veio
                </button>
            `);
            break;
            
        case 'Confirmado':
            acoes.push(`
                <button class="btn-concluir" onclick="alterarStatusReservaGarcom(${reserva.id}, 'Concluido')">
                    🍽️ Finalizar e Liberar Mesa
                </button>
            `);
            break;
            
        default:
            acoes.push('<p style="color: #666; font-style: italic;">Nenhuma ação disponível</p>');
    }
    
    return acoes.join('');
}

// Função específica para ações do garçom
async function alterarStatusReservaGarcom(reservaId, novoStatus) {
    let mensagemConfirmacao = '';
    
    switch(novoStatus) {
        case 'Confirmado':
            mensagemConfirmacao = 'Confirmar que o cliente chegou?';
            break;
        case 'Concluido':
            mensagemConfirmacao = 'Finalizar esta reserva e liberar a mesa?';
            break;
        case 'Expirado':
            mensagemConfirmacao = 'Marcar como expirada? (Cliente não compareceu)';
            break;
        default:
            mensagemConfirmacao = `Alterar status para ${novoStatus}?`;
    }
    
    if (!confirm(mensagemConfirmacao)) {
        return;
    }
    
    try {
        const response = await fetch('BackEnd/api/gerenciar-status-reservas.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                reserva_id: reservaId,
                novo_status: novoStatus,
                acao_garcom: true
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            let mensagemSucesso = '';
            switch(novoStatus) {
                case 'Confirmado':
                    mensagemSucesso = 'Cliente confirmado! Mesa ocupada.';
                    break;
                case 'Concluido':
                    mensagemSucesso = 'Reserva finalizada! Mesa liberada.';
                    break;
                case 'Expirado':
                    mensagemSucesso = 'Reserva expirada! Mesa liberada.';
                    break;
                default:
                    mensagemSucesso = data.mensagem;
            }
            
            mostrarMensagem(mensagemSucesso, 'success');
            
            // Recarregar todos os dados
            await Promise.all([
                carregarEstatisticasMesas(),
                carregarEstatisticasReservas(),
                carregarReservasParaGarcom()
            ]);
        } else {
            mostrarMensagem(data.erro || 'Erro ao alterar status', 'error');
        }
    } catch (error) {
        console.error('Erro ao alterar status da reserva:', error);
        mostrarMensagem('Erro interno do servidor', 'error');
    }
}

// Atualizar a função de inicialização para incluir reservas do garçom
const inicializarDashboardOriginal = inicializarDashboard;
inicializarDashboard = async function() {
    await inicializarDashboardOriginal();
    await carregarReservasParaGarcom();
};

// Atualizar a função de atualização automática para incluir reservas do garçom
function iniciarAtualizacaoAutomatica() {
    const intervalo = setInterval(async () => {
        try {
            await verificarReservasExpiradas();
            
            await Promise.all([
                carregarEstatisticasMesas(),
                carregarEstatisticasReservas(),
                carregarReservasPendentes(),
                carregarReservasParaGarcom() // Adicionar esta linha
            ]);
        } catch (error) {
            console.error('Erro na atualização automática:', error);
        }
    }, 30000);
    
    intervalosAtualizacao.push(intervalo);
}