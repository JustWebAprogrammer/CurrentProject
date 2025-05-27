// Carregar dados do usuário ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se está logado
    const clienteLogado = localStorage.getItem('clienteLogado');
    if (!clienteLogado) {
        alert('Você precisa fazer login primeiro');
        window.location.href = 'Login.html';
        return;
    }

    const userData = JSON.parse(clienteLogado);
    loadUserData(userData);
    loadReservations();
});

function loadUserData(userData = null) {
    if (!userData) {
        const clienteLogado = localStorage.getItem('clienteLogado');
        if (!clienteLogado) return;
        userData = JSON.parse(clienteLogado);
    }

    document.getElementById('nome-usuario').value = userData.nome;
    document.getElementById('email').value = userData.email;
    document.getElementById('telefone').value = userData.telemovel;
}

function toggleEdit(fieldId) {
    const input = document.getElementById(fieldId);
    const salvarBtn = document.getElementById('salvar-btn');
    const cancelarBtn = document.getElementById('cancelar-btn');
    
    if (input.readOnly) {
        input.readOnly = false;
        input.focus();
        salvarBtn.style.display = 'block';
        cancelarBtn.style.display = 'block';
    }
}

function cancelEdit() {
    // Recarregar dados originais
    loadUserData();
    
    // Tornar campos read-only novamente
    document.getElementById('nome-usuario').readOnly = true;
    document.getElementById('email').readOnly = true;
    document.getElementById('telefone').readOnly = true;
    
    // Esconder botões
    document.getElementById('salvar-btn').style.display = 'none';
    document.getElementById('cancelar-btn').style.display = 'none';
}

function saveChanges() {
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

    // Obter dados do cliente logado
    const clienteLogado = JSON.parse(localStorage.getItem('clienteLogado'));
    
    // Enviar dados para o backend
    const dadosAtualizacao = {
        id: clienteLogado.id,
        nome: nomeUsuario,
        email: email,
        telemovel: telefone
    };

    // Desabilitar botão durante salvamento
    const salvarBtn = document.getElementById('salvar-btn');
    salvarBtn.textContent = 'Salvando...';
    salvarBtn.disabled = true;

    fetch('BackEnd/api/perfil.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dadosAtualizacao)
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Atualizar dados no localStorage
            const clienteAtualizado = {
                ...clienteLogado,
                nome: nomeUsuario,
                email: email,
                telemovel: telefone
            };
            localStorage.setItem('clienteLogado', JSON.stringify(clienteAtualizado));
            
            alert('Dados salvos com sucesso!');
            
            // Tornar campos read-only novamente
            document.getElementById('nome-usuario').readOnly = true;
            document.getElementById('email').readOnly = true;
            document.getElementById('telefone').readOnly = true;
            
            // Esconder botões
            document.getElementById('salvar-btn').style.display = 'none';
            document.getElementById('cancelar-btn').style.display = 'none';
        } else {
            alert('Erro ao salvar dados: ' + (data.erro || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão. Tente novamente.');
    })
    .finally(() => {
        salvarBtn.textContent = 'Salvar Alterações';
        salvarBtn.disabled = false;
    });
}

function filterReservas(tipo) {
    // Remover classe active de todos os botões
    document.querySelectorAll('.historico-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Adicionar classe active ao botão clicado
    event.target.classList.add('active');
    
    console.log('Filtrando reservas por:', tipo);
    loadReservations(tipo);
}

function loadReservations(filtro = 'proximas') {
    const clienteLogado = JSON.parse(localStorage.getItem('clienteLogado'));
    if (!clienteLogado) return;

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
            // Se não conseguir carregar do backend, usar dados simulados
            console.warn('Usando dados simulados para reservas');
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
                },
                {
                    id: 3,
                    data: '2025-06-02',
                    hora: '18:30',
                    num_pessoas: 6,
                    status: 'Reservado'
                }
            ];
            displayReservations(reservasSimuladas);
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
        const podeModificar = canModifyReservation(reserva.data, reserva.hora);
        const tempoRestante = formatTimeRemaining(reserva.data, reserva.hora);
        
        const reservaCard = document.createElement('div');
        reservaCard.className = 'reserva-card';
        
        const statusClass = podeModificar ? '' : 'disabled';
        const statusMessage = podeModificar ? '' : `<p class="tempo-limite">${tempoRestante || 'Não é mais possível editar/cancelar'}</p>`;
        
        reservaCard.innerHTML = `
            <div class="reserva-info">
                <p><strong>Data:</strong> ${dataFormatada}</p>
                <p><strong>Horário:</strong> ${reserva.hora}</p>
                <p><strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
                <p><strong>Status:</strong> ${reserva.status}</p>
                ${statusMessage}
            </div>
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
        reservasLista.appendChild(reservaCard);
    });
}

function editarReserva(reservaId) {
    // Buscar dados da reserva específica
    fetch(`BackEnd/api/reservas.php?id=${reservaId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso && data.reserva) {
            const reserva = data.reserva;
            
            if (!canModifyReservation(reserva.data, reserva.hora)) {
                alert('Não é possível editar a reserva com menos de 2 horas de antecedência.');
                return;
            }
            
            // Redirecionar para página de reserva com parâmetros de edição
            const params = new URLSearchParams({
                edit: 'true',
                id: reserva.id,
                data: reserva.data,
                horario: reserva.hora,
                pessoas: reserva.num_pessoas
            });
            
            window.location.href = `Reserva.html?${params.toString()}`;
        } else {
            alert('Erro ao carregar dados da reserva.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão. Tente novamente.');
    });
}

function cancelarReserva(reservaId) {
    // Primeiro, verificar se pode cancelar
    fetch(`BackEnd/api/reservas.php?id=${reservaId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso && data.reserva) {
            const reserva = data.reserva;
            
            if (!canModifyReservation(reserva.data, reserva.hora)) {
                alert('Não é possível cancelar a reserva com menos de 2 horas de antecedência.');
                return;
            }
            
            if (confirm('Tem certeza que deseja cancelar esta reserva?')) {
                // Proceder com o cancelamento
                fetch('BackEnd/api/reservas.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reservaId })
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
        } else {
            alert('Erro ao carregar dados da reserva.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de conexão. Tente novamente.');
    });
}

function navigateTo(page) {
    if (page === 'Login.html') {
        // Se for para login, fazer logout
        localStorage.removeItem('clienteLogado');
    }
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

// Event listeners
document.getElementById('salvar-btn').addEventListener('click', saveChanges);
document.getElementById('cancelar-btn').addEventListener('click', cancelEdit);