// Carregar dados do usuário ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    loadReservations();
});

function toggleEdit(fieldId) {
    const input = document.getElementById(fieldId);
    const formActions = document.querySelector('.form-actions');
    
    if (input.readOnly) {
        input.readOnly = false;
        input.focus();
        formActions.style.display = 'block';
    }
}

function cancelEdit() {
    // Recarregar dados originais
    document.getElementById('nome-usuario').value = clienteLogado.nome;
    document.getElementById('email').value = clienteLogado.email;
    document.getElementById('telefone').value = clienteLogado.telemovel;
    
    // Tornar campos read-only novamente
    document.getElementById('nome-usuario').readOnly = true;
    document.getElementById('email').readOnly = true;
    document.getElementById('telefone').readOnly = true;
    
    // Esconder botões
    document.querySelector('.form-actions').style.display = 'none';
}

// Interceptar envio do formulário
document.getElementById('perfil-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar dados antes de salvar
    const nomeUsuario = document.getElementById('nome-usuario').value.trim();
    const email = document.getElementById('email').value.trim();
    const telefone = document.getElementById('telefone').value.trim();
    
    // Validação básica
    if (nomeUsuario.length < 3) {
        alert('Nome de usuário deve ter pelo menos 3 caracteres.');
        return;
    }
    
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        alert('Por favor, insira um e-mail válido.');
        return;
    }

    // Validar telefone (9 dígitos)
    const telefoneNumeros = telefone.replace(/\D/g, '');
    if (telefoneNumeros.length !== 9) {
        alert('O telefone deve ter exatamente 9 dígitos.');
        return;
    }

    // Desabilitar botão durante salvamento
    const salvarBtn = document.getElementById('salvar-btn');
    salvarBtn.textContent = 'Salvando...';
    salvarBtn.disabled = true;

    // Enviar formulário
    this.submit();
});

function filterReservas(tipo) {
    // Remover classe active de todos os botões
    document.querySelectorAll('.historico-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Adicionar classe active ao botão clicado
    event.target.classList.add('active');
    
    loadReservations(tipo);
}

function loadReservations(filtro = 'proximas') {
    // Buscar reservas do backend
    fetch(`BackEnd/api/reservas.php?cliente_id=${clienteLogado.id}&filtro=${filtro}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            displayReservations(data.reservas);
        } else {
            // Se não conseguir carregar do backend, mostrar mensagem
            console.warn('Erro ao carregar reservas:', data.erro);
            const reservasLista = document.getElementById('reservas-lista');
            reservasLista.innerHTML = '<p class="sem-reservas">Erro ao carregar reservas. Tente novamente.</p>';
        }
    })
    .catch(error => {
        console.error('Erro ao carregar reservas:', error);
        // Usar dados simulados em caso de erro
        const reservasSimuladas = [
            {
                id: 1,
                data: '2025-05-28',
                hora: '19:30',
                num_pessoas: 4,
                status: 'Reservado'
            },
            {
                id: 2,
                data: '2025-05-30',
                hora: '20:00',
                num_pessoas: 2,
                status: 'Reservado'
            }
        ];
        displayReservations(reservasSimuladas);
    });
}

function displayReservations(reservas) {
    const reservasLista = document.getElementById('reservas-lista');
    reservasLista.innerHTML = '';
    
    if (reservas.length === 0) {
        reservasLista.innerHTML = '<p class="sem-reservas">Você não possui reservas no momento.</p>';
        return;
    }

    reservas.forEach((reserva) => {
        const dataFormatada = formatarData(reserva.data);
        const podeModificar = canModifyReservation(reserva.data, reserva.hora) && 
                             reserva.status === 'Reservado'; // Só pode modificar se estiver "Reservado"
        const tempoRestante = formatTimeRemaining(reserva.data, reserva.hora);
        
        const reservaCard = document.createElement('div');
        reservaCard.className = 'reserva-card';
        
        // Adicionar classes baseadas no status
        if (reserva.status === 'Cancelado') {
            reservaCard.classList.add('reserva-cancelada');
        } else if (reserva.status === 'Expirado') {
            reservaCard.classList.add('reserva-expirada');
        }
        
        let statusMessage = '';
        let statusClass = '';
        
        switch(reserva.status) {
            case 'Cancelado':
                statusMessage = '<p class="status-cancelado">Reserva cancelada</p>';
                statusClass = 'status-cancelado';
                break;
            case 'Expirado':
                statusMessage = '<p class="status-expirado">Reserva expirada</p>';
                statusClass = 'status-expirado';
                break;
            case 'Concluído':
                statusMessage = '<p class="status-concluido">Reserva concluída</p>';
                statusClass = 'status-concluido';
                break;
            default:
                if (!podeModificar && reserva.status === 'Reservado') {
                    statusMessage = `<p class="tempo-limite">${tempoRestante || 'Não é mais possível editar/cancelar'}</p>`;
                }
        }
        
        // Mostrar botões apenas para reservas ativas e que podem ser modificadas
        let botoesAcoes = '';
        if (reserva.status === 'Reservado' && podeModificar) {
            botoesAcoes = `
                <div class="reserva-acoes">
                    <button class="editar-reserva" 
                            onclick="editarReserva(${reserva.id})">
                        Editar
                    </button>
                    <button class="cancelar-reserva" 
                            onclick="cancelarReserva(${reserva.id})">
                        Cancelar
                    </button>
                </div>
            `;
        }
        
        reservaCard.innerHTML = `
            <div class="reserva-info">
                <p><strong>Data:</strong> ${dataFormatada}</p>
                <p><strong>Horário:</strong> ${reserva.hora}</p>
                <p><strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
                <p><strong>Status:</strong> <span class="${statusClass}">${reserva.status}</span></p>
                ${statusMessage}
            </div>
            ${botoesAcoes}
        `;
        reservasLista.appendChild(reservaCard);
    });
}

function editarReserva(reservaId) {
    // Buscar a reserva na lista atual em vez de fazer nova requisição
    const reservasLista = document.getElementById('reservas-lista');
    const reservaCards = reservasLista.querySelectorAll('.reserva-card');
    let reservaData = null;
    
    // Encontrar a reserva na lista atual
    reservaCards.forEach(card => {
        const botaoEditar = card.querySelector('.editar-reserva');
        if (botaoEditar && botaoEditar.getAttribute('onclick').includes(reservaId)) {
            const info = card.querySelector('.reserva-info');
            const paragrafos = info.querySelectorAll('p');
            
            // Extrair dados de forma mais robusta
            let data, hora, numPessoas;
            
            paragrafos.forEach(p => {
                const texto = p.textContent || p.innerText;
                if (texto.includes('Data:')) {
                    data = texto.replace('Data:', '').trim();
                } else if (texto.includes('Horário:')) {
                    hora = texto.replace('Horário:', '').trim();
                } else if (texto.includes('Pessoas:')) {
                    numPessoas = texto.replace('Pessoas:', '').trim();
                }
            });
            
            if (data && hora && numPessoas) {
                reservaData = {
                    id: reservaId,
                    data: data,
                    hora: hora,
                    num_pessoas: parseInt(numPessoas)
                };
            }
        }
    });
    
    if (!reservaData) {
        alert('Erro ao carregar dados da reserva.');
        console.error('Dados da reserva não encontrados para ID:', reservaId);
        return;
    }
    
    // Verificar se ainda pode ser editada
    if (!canModifyReservation(reservaData.data, reservaData.hora)) {
        alert('Não é possível editar a reserva com menos de 2 horas de antecedência.');
        return;
    }
    
    // Converter data para formato YYYY-MM-DD se necessário
    let dataFormatted = reservaData.data;
    if (reservaData.data.includes('/')) {
        const [dia, mes, ano] = reservaData.data.split('/');
        dataFormatted = `${ano}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`;
    }
    
    // Debug para verificar os dados
    console.log('Dados da reserva para edição:', {
        id: reservaData.id,
        data: dataFormatted,
        hora: reservaData.hora,
        pessoas: reservaData.num_pessoas
    });
    
    // Redirecionar para página de reserva com parâmetros de edição
    const params = new URLSearchParams({
        edit: 'true',
        id: reservaData.id,
        data: dataFormatted,
        horario: reservaData.hora,
        pessoas: reservaData.num_pessoas
    });
    
    window.location.href = `Reserva.html?${params.toString()}`;
}

function cancelarReserva(reservaId) {
    if (confirm('Tem certeza que deseja cancelar esta reserva?')) {
        fetch(`BackEnd/api/reservas.php?reserva_id=${reservaId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('Reserva cancelada com sucesso!');
                // Recarregar as reservas mantendo o filtro atual
                const activeButton = document.querySelector('.historico-btn.active');
                const filtroAtual = activeButton ? getFiltroFromButton(activeButton) : 'proximas';
                loadReservations(filtroAtual);
            } else {
                alert('Erro ao cancelar reserva: ' + (data.erro || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro de conexão. Tente novamente.');
        });
    }
}

// Função auxiliar para obter o filtro do botão
function getFiltroFromButton(button) {
    const texto = button.textContent.toLowerCase();
    switch(texto) {
        case 'próximas': return 'proximas';
        case 'passadas': return 'passadas';
        case 'canceladas': return 'canceladas';
        default: return 'proximas';
    }
}

function navigateTo(page) {
    window.location.href = page;
}

function canModifyReservation(reservaData, reservaHorario) {
    const agora = new Date();
    
    // Se a data está no formato YYYY-MM-DD, converter para o formato esperado
    let dataParaComparacao;
    if (reservaData.includes('-')) {
        dataParaComparacao = new Date(reservaData + 'T' + reservaHorario);
    } else {
        // Se está no formato DD/MM/YYYY
        dataParaComparacao = new Date(reservaData.split('/').reverse().join('-') + 'T' + reservaHorario);
    }
    
    const diferencaHoras = (dataParaComparacao - agora) / (1000 * 60 * 60); // Diferença em horas
    
    return diferencaHoras >= 2; // Permite modificação apenas se faltam 2+ horas
}

function formatTimeRemaining(reservaData, reservaHorario) {
    const agora = new Date();
    
    let dataParaComparacao;
    if (reservaData.includes('-')) {
        dataParaComparacao = new Date(reservaData + 'T' + reservaHorario);
    } else {
        dataParaComparacao = new Date(reservaData.split('/').reverse().join('-') + 'T' + reservaHorario);
    }
    
    const diferencaHoras = (dataParaComparacao - agora) / (1000 * 60 * 60);
    
    if (diferencaHoras < 2) {
        const minutosRestantes = Math.floor((dataParaComparacao - agora) / (1000 * 60));
        if (minutosRestantes > 0) {
            return `Faltam ${minutosRestantes} minutos`;
        } else {
            return 'Reserva expirada';
        }
    }
    return '';
}

function formatarData(data) {
    // Se a data está no formato YYYY-MM-DD, converter para DD/MM/YYYY
    if (data.includes('-')) {
        const partes = data.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }
    return data; // Se já está no formato correto
}
function editarReserva(reservaId) {
    // Buscar dados da reserva para edição
    fetch(`BackEnd/api/reservas.php?reserva_id=${reservaId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso && data.reservas && data.reservas.length > 0) {
            const reserva = data.reservas.find(r => r.id == reservaId);
            if (reserva) {
                // Redirecionar para página de reserva em modo de edição
                const params = new URLSearchParams({
                    edit: 'true',
                    id: reserva.id,
                    data: reserva.data,
                    horario: reserva.hora,
                    pessoas: reserva.num_pessoas
                });
                
                window.location.href = `Reserva.html?${params.toString()}`;
            } else {
                alert('Reserva não encontrada.');
            }
        } else {
            alert('Erro ao carregar dados da reserva: ' + (data.erro || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao buscar reserva:', error);
        alert('Erro de conexão. Tente novamente.');
    });
}