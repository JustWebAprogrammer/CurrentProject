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
        const podeModificar = canModifyReservation(reserva.data, reserva.hora) && reserva.status !== 'Cancelado';
        const tempoRestante = formatTimeRemaining(reserva.data, reserva.hora);
        
        const reservaCard = document.createElement('div');
        reservaCard.className = 'reserva-card';
        
        // Adicionar classe para reservas canceladas
        if (reserva.status === 'Cancelado') {
            reservaCard.classList.add('reserva-cancelada');
        }
        
        const statusClass = podeModificar ? '' : 'disabled';
        let statusMessage = '';
        
        if (reserva.status === 'Cancelado') {
            statusMessage = '<p class="status-cancelado">Reserva cancelada</p>';
        } else if (!podeModificar && reserva.status !== 'Cancelado') {
            statusMessage = `<p class="tempo-limite">${tempoRestante || 'Não é mais possível editar/cancelar'}</p>`;
        }
        
        // Mostrar botões apenas para reservas não canceladas
        let botoesAcoes = '';
        if (reserva.status !== 'Cancelado') {
            botoesAcoes = `
                <div class="reserva-acoes">
                    <button class="editar-reserva ${statusClass}" 
                            onclick="editarReserva(${reserva.id})" 
                            ${!podeModificar ? 'disabled title="Não é possível editar com menos de 2h de antecedência"' : ''}>
                        Editar
                    </button>
                    <button class="cancelar-reserva ${statusClass}" 
                            onclick="cancelarReserva(${reserva.id})" 
                            ${!podeModificar ? 'disabled title="Não é possível cancelar com menos de 2h de antecedência"' : ''}>
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
                <p><strong>Status:</strong> ${reserva.status}</p>
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
            const dataText = info.children[0].textContent.replace('Data: ', '');
            const horaText = info.children[1].textContent.replace('Horário: ', '');
            const pessoasText = info.children[2].textContent.replace('Pessoas: ', '');
            
            reservaData = {
                id: reservaId,
                data: dataText,
                hora: horaText,
                num_pessoas: pessoasText
            };
        }
    });
    
    if (!reservaData) {
        alert('Erro ao carregar dados da reserva.');
        return;
    }
    
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
        // Proceder com o cancelamento
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
                loadReservations(); // Recarregar a lista
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