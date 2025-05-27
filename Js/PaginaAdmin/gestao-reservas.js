let clientes = [];

document.addEventListener('DOMContentLoaded', function() {
    carregarClientes();
    carregarReservas();
    verificarMensagens();
    configurarDataMinima();
});

function verificarMensagens() {
    const urlParams = new URLSearchParams(window.location.search);
    const erro = urlParams.get('erro');
    const sucesso = urlParams.get('sucesso');
    
    if (erro) {
        alert('Erro: ' + erro);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (sucesso) {
        alert('Sucesso: ' + sucesso);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
}

function configurarDataMinima() {
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('data-reserva').min = hoje;
}

async function carregarClientes() {
    try {
        const response = await fetch('BackEnd/api/admin-usuarios.php?acao=clientes');
        const data = await response.json();
        
        if (data.erro) {
            console.error('Erro ao carregar clientes:', data.erro);
            return;
        }

        clientes = data;
        const select = document.getElementById('cliente-reserva');
        select.innerHTML = '<option value="">Selecione um cliente</option>';
        
        data.forEach(cliente => {
            const option = document.createElement('option');
            option.value = cliente.id;
            option.textContent = `${cliente.nome} - ${cliente.email}`;
            select.appendChild(option);
        });

    } catch (error) {
        console.error('Erro na requisição:', error);
    }
}

async function carregarReservas() {
    const filtro = document.getElementById('filtro-reservas').value;
    
    try {
        const response = await fetch(`BackEnd/api/admin-reservas.php?acao=reservas&filtro=${filtro}`);
        const data = await response.json();
        
        if (data.erro) {
            console.error('Erro ao carregar reservas:', data.erro);
            return;
        }

        renderizarReservas(data);

    } catch (error) {
        console.error('Erro na requisição:', error);
    }
}

function renderizarReservas(reservas) {
    const container = document.getElementById('lista-reservas');
    
    if (!reservas || reservas.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #666; padding: 20px;">Nenhuma reserva encontrada</p>';
        return;
    }

    let html = '';
    reservas.forEach(reserva => {
        const statusClass = getStatusClass(reserva.status);
        const dataFormatada = new Date(reserva.data).toLocaleDateString();
        
        html += `
            <div class="reserva-item">
                <div class="reserva-info">
                    <h4>${reserva.cliente_nome}</h4>
                    <p><strong>Data:</strong> ${dataFormatada} às ${reserva.hora}</p>
                    <p><strong>Mesa:</strong> ${reserva.mesa_id} (${reserva.mesa_capacidade} pessoas)</p>
                    <p><strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
                    <p><strong>Email:</strong> ${reserva.cliente_email}</p>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="reserva-status ${statusClass}">${reserva.status}</span>
                    ${reserva.status === 'Reservado' ? 
                        `<button class="cancelar-btn" onclick="cancelarReserva(${reserva.id})">Cancelar</button>` : 
                        ''
                    }
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function getStatusClass(status) {
    switch(status) {
        case 'Reservado': return 'status-reservado';
        case 'Concluído': return 'status-concluido';
        case 'Cancelado': return 'status-cancelado';
        case 'Expirado': return 'status-expirado';
        default: return '';
    }
}

function abrirModalReserva() {
    document.getElementById('modal-reserva').style.display = 'block';
    document.getElementById('form-reserva').reset();
    configurarDataMinima();
}

function fecharModalReserva() {
    document.getElementById('modal-reserva').style.display = 'none';
}

async function verificarMesasDisponiveis() {
    const data = document.getElementById('data-reserva').value;
    const hora = document.getElementById('hora-reserva').value;
    const numPessoas = document.getElementById('pessoas-reserva').value;
    
    if (!data || !hora || !numPessoas) {
        return;
    }
    
    try {
        const response = await fetch(`BackEnd/api/admin-reservas.php?acao=mesas_disponiveis&data=${data}&hora=${hora}&num_pessoas=${numPessoas}`);
        const mesas = await response.json();
        
        const select = document.getElementById('mesa-reserva');
        select.innerHTML = '';
        
        if (mesas.erro) {
            select.innerHTML = '<option value="">Erro ao carregar mesas</option>';
            return;
        }
        
        if (mesas.length === 0) {
            select.innerHTML = '<option value="">Nenhuma mesa disponível</option>';
            return;
        }
        
        select.innerHTML = '<option value="">Selecione uma mesa</option>';
        mesas.forEach(mesa => {
            const option = document.createElement('option');
            option.value = mesa.id;
            option.textContent = `Mesa ${mesa.id} (${mesa.capacidade} pessoas)`;
            select.appendChild(option);
        });

    } catch (error) {
        console.error('Erro ao verificar mesas:', error);
    }
}

// Event listeners para atualizar mesas quando dados mudarem
document.getElementById('data-reserva').addEventListener('change', verificarMesasDisponiveis);
document.getElementById('hora-reserva').addEventListener('change', verificarMesasDisponiveis);
document.getElementById('pessoas-reserva').addEventListener('change', verificarMesasDisponiveis);

async function criarReserva() {
    const clienteId = document.getElementById('cliente-reserva').value;
    const mesaId = document.getElementById('mesa-reserva').value;
    const data = document.getElementById('data-reserva').value;
    const hora = document.getElementById('hora-reserva').value;
    const numPessoas = document.getElementById('pessoas-reserva').value;
    
    if (!clienteId || !mesaId || !data || !hora || !numPessoas) {
        alert('Por favor, preencha todos os campos.');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('acao', 'criar_reserva');
        formData.append('cliente_id', clienteId);
        formData.append('mesa_id', mesaId);
        formData.append('data', data);
        formData.append('hora', hora);
        formData.append('num_pessoas', numPessoas);

        const response = await fetch('BackEnd/api/admin-reservas.php', {
            method: 'POST',
            body: formData
        });

        setTimeout(() => {
            window.location.reload();
        }, 100);

    } catch (error) {
        console.error('Erro ao criar reserva:', error);
        alert('Erro ao criar reserva. Tente novamente.');
    }
}

async function cancelarReserva(reservaId) {
    if (!confirm('Tem certeza que deseja cancelar esta reserva?')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('acao', 'cancelar_reserva');
        formData.append('reserva_id', reservaId);

        const response = await fetch('BackEnd/api/admin-reservas.php', {
            method: 'POST',
            body: formData
        });

        setTimeout(() => {
            window.location.reload();
        }, 100);

    } catch (error) {
        console.error('Erro ao cancelar reserva:', error);
        alert('Erro ao cancelar reserva. Tente novamente.');
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('modal-reserva');
    if (event.target === modal) {
        fecharModalReserva();
    }
}