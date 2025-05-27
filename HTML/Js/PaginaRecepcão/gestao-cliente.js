// Variáveis globais
let clienteSelecionado = null;
let mesasSelecionadas = [];
let numPessoasAtual = 0;
let mesasDisponiveis = [];

// Função para limitar input de número
function limitarNumeroInput(input, maxValue = 60) {
    input.addEventListener('input', function(e) {
        let value = parseInt(e.target.value);
        
        if (value > maxValue) {
            e.target.value = maxValue;
        }
        
        if (value < 1 && e.target.value !== '') {
            e.target.value = 1;
        }
    });
    
    input.addEventListener('keydown', function(e) {
        const currentValue = e.target.value;
        const key = e.key;
        
        if (['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(key)) {
            return;
        }
        
        if (!/\d/.test(key)) {
            e.preventDefault();
            return;
        }
        
        const newValue = currentValue + key;
        if (parseInt(newValue) > maxValue) {
            e.preventDefault();
        }
    });
}

// Função para calcular número de mesas necessárias
function calcularMesasNecessarias(numPessoas) {
    if (numPessoas <= 4) {
        return 1;
    }
    return Math.ceil(numPessoas / 4);
}

// Função para distribuir pessoas nas mesas
function distribuirPessoas(numPessoas, numMesas) {
    const distribuicao = [];
    const pessoasPorMesa = Math.floor(numPessoas / numMesas);
    const pessoasRestantes = numPessoas % numMesas;
    
    for (let i = 0; i < numMesas; i++) {
        let pessoas = pessoasPorMesa;
        if (i < pessoasRestantes) {
            pessoas += 1;
        }
        distribuicao.push(Math.min(pessoas, 4));
    }
    
    return distribuicao;
}

// Buscar cliente
async function buscarCliente() {
    const tipoBusca = document.getElementById('tipo-busca').value;
    const termoBusca = document.getElementById('campo-busca').value.trim();
    
    if (termoBusca.length < 2) {
        document.getElementById('resultados-busca').style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch(`BackEnd/api/buscar-clientes.php?tipo=${tipoBusca}&termo=${encodeURIComponent(termoBusca)}`);
        const data = await response.json();
        
        if (data.sucesso) {
            mostrarResultadosBusca(data.clientes);
        } else {
            console.error('Erro ao buscar clientes:', data.erro);
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
    }
}

// Mostrar resultados da busca
function mostrarResultadosBusca(clientes) {
    const resultadosDiv = document.getElementById('resultados-busca');
    const listaDiv = document.getElementById('lista-clientes');
    
    if (clientes.length === 0) {
        listaDiv.innerHTML = '<p>Nenhum cliente encontrado.</p>';
    } else {
        listaDiv.innerHTML = clientes.map(cliente => `
            <div class="cliente-item" onclick="selecionarCliente(${cliente.id})">
                <h4>${cliente.nome}</h4>
                <p>Email: ${cliente.email}</p>
                <p>Telefone: ${cliente.telemovel}</p>
                <p>Cliente desde: ${new Date(cliente.data_criacao).toLocaleDateString('pt-BR')}</p>
            </div>
        `).join('');
    }
    
    resultadosDiv.style.display = 'block';
}

// Selecionar cliente
async function selecionarCliente(clienteId) {
    try {
        // Buscar detalhes do cliente
        const response = await fetch(`BackEnd/api/buscar-clientes.php?cliente_id=${clienteId}`);
        const data = await response.json();
        
        if (data.sucesso && data.clientes.length > 0) {
            clienteSelecionado = data.clientes[0];
            mostrarDetalhesCliente(clienteSelecionado);
            
            // Buscar reservas do cliente
            await carregarReservasCliente(clienteId);
        }
    } catch (error) {
        console.error('Erro ao carregar cliente:', error);
    }
}

// Mostrar detalhes do cliente
function mostrarDetalhesCliente(cliente) {
    document.getElementById('cliente-nome').textContent = cliente.nome;
    document.getElementById('cliente-email').textContent = cliente.email;
    document.getElementById('cliente-telefone').textContent = cliente.telemovel;
    document.getElementById('cliente-data').textContent = new Date(cliente.data_criacao).toLocaleDateString('pt-BR');
    
    document.getElementById('detalhes-cliente').style.display = 'block';
}

// Carregar reservas do cliente
async function carregarReservasCliente(clienteId) {
    try {
        const response = await fetch(`BackEnd/api/reservas.php?cliente_id=${clienteId}&filtro=proximas`);
        const data = await response.json();
        
        if (data.sucesso) {
            mostrarReservasCliente(data.reservas);
        }
    } catch (error) {
        console.error('Erro ao carregar reservas:', error);
    }
}

// Mostrar reservas do cliente
function mostrarReservasCliente(reservas) {
    const listaDiv = document.getElementById('reservas-lista');
    
    if (reservas.length === 0) {
        listaDiv.innerHTML = '<p>Este cliente não possui reservas.</p>';
        return;
    }
    
    listaDiv.innerHTML = reservas.map(reserva => `
        <div class="reserva-item">
            <h4>Reserva #${reserva.id}</h4>
            <p><strong>Data:</strong> ${new Date(reserva.data).toLocaleDateString('pt-BR')}</p>
            <p><strong>Horário:</strong> ${reserva.hora}</p>
            <p><strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
            <p><strong>Mesa:</strong> ${reserva.mesa_id}</p>
            <p><strong>Status:</strong> ${reserva.status}</p>
        </div>
    `).join('');
}

// Atualizar cálculo de mesas
function atualizarCalculoMesas() {
    const numPessoas = parseInt(document.getElementById('num-pessoas-walkin').value) || 0;
    numPessoasAtual = numPessoas;
    
    // Limpar seleções anteriores
    mesasSelecionadas = [];
    document.querySelectorAll('.mesa-card').forEach(card => {
        card.classList.remove('selected', 'selected-multiple');
    });
    
    // Remover aviso anterior se existir
    const avisoExistente = document.querySelector('.mesas-calculadas');
    if (avisoExistente) {
        avisoExistente.remove();
    }
    
    if (numPessoas > 0) {
        mostrarCalculoMesas(numPessoas);
    }
    
    atualizarBotaoOcupar();
}

// Mostrar cálculo de mesas
function mostrarCalculoMesas(numPessoas) {
    const mesasNecessarias = calcularMesasNecessarias(numPessoas);
    const distribuicao = distribuirPessoas(numPessoas, mesasNecessarias);
    
    const infoDiv = document.createElement('div');
    infoDiv.className = 'mesas-calculadas';
    
    let avisoUniao = '';
    if (mesasNecessarias > 1) {
        avisoUniao = `
            <div class="aviso-uniao">
                <strong>💡 Dica:</strong> Para ${numPessoas} pessoas, as mesas selecionadas deverão ser unidas no salão conforme disponibilidade do espaço.
            </div>
        `;
    }
    
    infoDiv.innerHTML = `
        <h4>📊 Cálculo de Mesas para ${numPessoas} pessoas</h4>
        <div class="mesa-necessarias">
            <strong>Mesas necessárias:</strong> ${mesasNecessarias} mesa${mesasNecessarias > 1 ? 's' : ''}
        </div>
        ${avisoUniao}
        <p><strong>Distribuição sugerida:</strong></p>
        <div class="distribuicao-pessoas">
            ${distribuicao.map((pessoas, index) => `
                <div class="mesa-distribuicao">
                    Mesa ${index + 1}<br>
                    <strong>${pessoas} pessoa${pessoas > 1 ? 's' : ''}</strong>
                </div>
            `).join('')}
        </div>
        <p><strong>Selecione ${mesasNecessarias} mesa${mesasNecessarias > 1 ? 's' : ''} disponível${mesasNecessarias > 1 ? 'eis' : ''} abaixo:</strong></p>
    `;
    
    const mesasGrid = document.getElementById('mesas-grid');
    mesasGrid.parentNode.insertBefore(infoDiv, mesasGrid);
}

// Selecionar mesa
function selecionarMesa(mesaId) {
    if (numPessoasAtual === 0) {
        alert('Por favor, informe primeiro o número de pessoas.');
        return;
    }
    
    const mesasNecessarias = calcularMesasNecessarias(numPessoasAtual);
    const mesaCard = document.querySelector(`[onclick="selecionarMesa(${mesaId})"]`);
    
    if (mesasSelecionadas.includes(mesaId)) {
        mesasSelecionadas = mesasSelecionadas.filter(id => id !== mesaId);
        mesaCard.classList.remove('selected', 'selected-multiple');
    } else {
        if (mesasSelecionadas.length >= mesasNecessarias) {
            alert(`Você já selecionou ${mesasNecessarias} mesa${mesasNecessarias > 1 ? 's' : ''}, que é o necessário para ${numPessoasAtual} pessoas.`);
            return;
        }
        
        mesasSelecionadas.push(mesaId);
        
        if (mesasNecessarias === 1) {
            mesaCard.classList.add('selected');
        } else {
            mesaCard.classList.add('selected-multiple');
        }
    }
    
    atualizarBotaoOcupar();
}

// Atualizar botão ocupar
function atualizarBotaoOcupar() {
    const botaoOcupar = document.querySelector('.ocupar-btn');
    const mesasNecessarias = calcularMesasNecessarias(numPessoasAtual);
    
    if (numPessoasAtual > 0 && mesasSelecionadas.length === mesasNecessarias) {
        botaoOcupar.disabled = false;
        botaoOcupar.textContent = `Ocupar ${mesasSelecionadas.length} Mesa${mesasSelecionadas.length > 1 ? 's' : ''} Selecionada${mesasSelecionadas.length > 1 ? 's' : ''}`;
    } else {
        botaoOcupar.disabled = true;
        if (numPessoasAtual > 0) {
            const faltam = mesasNecessarias - mesasSelecionadas.length;
            botaoOcupar.textContent = `Selecione mais ${faltam} mesa${faltam > 1 ? 's' : ''} (${mesasSelecionadas.length}/${mesasNecessarias})`;
        } else {
            botaoOcupar.textContent = 'Ocupar Mesa Selecionada';
        }
    }
}

// Ocupar mesa
async function ocuparMesa() {
    if (mesasSelecionadas.length === 0) {
        alert('Por favor, selecione pelo menos uma mesa.');
        return;
    }
    
    const nomeCliente = document.getElementById('cliente-walkin').value.trim();
    const numPessoas = parseInt(document.getElementById('num-pessoas-walkin').value);
    
    try {
        const response = await fetch('BackEnd/api/mesas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                mesas_ids: mesasSelecionadas,
                cliente_nome: nomeCliente,
                num_pessoas: numPessoas
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            alert(data.mensagem);
            cancelarOcupacao();
            await carregarMesas();
            await carregarMesasOcupadas();
        } else {
            alert('Erro: ' + data.erro);
        }
    } catch (error) {
        console.error('Erro ao ocupar mesa:', error);
        alert('Erro ao ocupar mesa. Tente novamente.');
    }
}

// Cancelar ocupação
function cancelarOcupacao() {
    document.getElementById('cliente-walkin').value = '';
    document.getElementById('num-pessoas-walkin').value = '';
    document.querySelectorAll('.mesa-card').forEach(card => {
        card.classList.remove('selected', 'selected-multiple');
    });
    mesasSelecionadas = [];
    numPessoasAtual = 0;
    
    const avisoExistente = document.querySelector('.mesas-calculadas');
    if (avisoExistente) {
        avisoExistente.remove();
    }
    
    atualizarBotaoOcupar();
}

// Carregar mesas disponíveis
async function carregarMesas() {
    try {
        const response = await fetch('BackEnd/api/mesas.php?acao=disponiveis');
        const data = await response.json();
        
        if (data.sucesso) {
            mesasDisponiveis = data.mesas;
            mostrarMesasDisponiveis(data.mesas);
        }
    } catch (error) {
        console.error('Erro ao carregar mesas:', error);
    }
}

// Mostrar mesas disponíveis
function mostrarMesasDisponiveis(mesas) {
    const mesasGrid = document.getElementById('mesas-grid');
    
    mesasGrid.innerHTML = mesas.map(mesa => `
        <div class="mesa-card" onclick="selecionarMesa(${mesa.id})">
            <h4>Mesa ${mesa.id}</h4>
            <p>Capacidade: ${mesa.capacidade}</p>
            <p>Disponível</p>
        </div>
    `).join('');
}

// Carregar mesas ocupadas
async function carregarMesasOcupadas() {
    try {
        const response = await fetch('BackEnd/api/mesas.php?acao=ocupadas');
        const data = await response.json();
        
        if (data.sucesso) {
            mostrarMesasOcupadas(data.mesas);
        }
    } catch (error) {
        console.error('Erro ao carregar mesas ocupadas:', error);
    }
}

// Mostrar mesas ocupadas
function mostrarMesasOcupadas(mesas) {
    const mesasOcupadasLista = document.getElementById('mesas-ocupadas-lista');
    
    if (mesas.length === 0) {
        mesasOcupadasLista.innerHTML = '<p>Nenhuma mesa ocupada no momento.</p>';
        return;
    }
    
    mesasOcupadasLista.innerHTML = mesas.map(mesa => `
        <div class="mesa-ocupada-item">
            <div class="mesa-ocupada-info">
                <h4>Mesa ${mesa.id} (${mesa.num_pessoas || 'N/A'} pessoas)</h4>
                <p><strong>Cliente:</strong> ${mesa.cliente_nome || 'Walk-in'}</p>
                <p><strong>Data:</strong> ${mesa.data ? new Date(mesa.data).toLocaleDateString('pt-BR') : 'Hoje'}</p>
                <p><strong>Horário:</strong> ${mesa.hora || 'N/A'}</p>
            </div>
            <button class="liberar-btn" onclick="liberarMesa(${mesa.id})">Liberar Mesa</button>
        </div>
    `).join('');
}

// Liberar mesa
async function liberarMesa(mesaId) {
    if (confirm(`Tem certeza que deseja liberar a Mesa ${mesaId}?`)) {
        try {
            const response = await fetch('BackEnd/api/mesas.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    mesas_ids: [mesaId]
                })
            });
            
            const data = await response.json();
            
            if (data.sucesso) {
                alert(data.mensagem);
                await carregarMesas();
                await carregarMesasOcupadas();
            } else {
                alert('Erro: ' + data.erro);
            }
        } catch (error) {
            console.error('Erro ao liberar mesa:', error);
            alert('Erro ao liberar mesa. Tente novamente.');
        }
    }
}

// Atualizar placeholder de busca
function updateSearchPlaceholder() {
    const tipoBusca = document.getElementById('tipo-busca').value;
    const campoBusca = document.getElementById('campo-busca');
    
    switch(tipoBusca) {
        case 'nome':
            campoBusca.placeholder = 'Digite o nome do cliente...';
            break;
        case 'email':
            campoBusca.placeholder = 'Digite o email do cliente...';
            break;
        case 'telefone':
            campoBusca.placeholder = 'Digite o telefone do cliente...';
            break;
        default:
            campoBusca.placeholder = 'Digite o nome do cliente...';
    }
}

// Adicionar event listener para mudanças no número de pessoas
document.addEventListener('DOMContentLoaded', function() {
    carregarMesas();
    carregarMesasOcupadas();
    
    // Monitorar mudanças no campo de número de pessoas
    const numPessoasInput = document.getElementById('num-pessoas-walkin');
    if (numPessoasInput) {
        limitarNumeroInput(numPessoasInput, 60);
        numPessoasInput.addEventListener('input', atualizarCalculoMesas);
        numPessoasInput.addEventListener('change', atualizarCalculoMesas);
    }
});
