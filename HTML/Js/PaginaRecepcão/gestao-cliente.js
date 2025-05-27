// Site/HTML/Js/Recepcionista/gestao-clientes.js - Versão Atualizada

// Dados simulados das mesas (todas com capacidade padrão de 4)
const mesasSimuladas = [
    { id: 1, capacidade: 4, estado: 'Livre' },
    { id: 2, capacidade: 4, estado: 'Ocupada', cliente: 'João Silva', pessoas: 30, horaOcupacao: '18:30' },
    { id: 3, capacidade: 4, estado: 'Livre' },
    { id: 4, capacidade: 4, estado: 'Livre' },
    { id: 5, capacidade: 4, estado: 'Livre'},
    { id: 6, capacidade: 4, estado: 'Livre' },
    { id: 7, capacidade: 4, estado: 'Livre' },
    { id: 8, capacidade: 4, estado: 'Livre' },
    { id: 9, capacidade: 4, estado: 'Livre' },
    { id: 10, capacidade: 4, estado: 'Livre' },
    { id: 11, capacidade: 4, estado: 'Livre' },
    { id: 12, capacidade: 4, estado: 'Livre' },
    { id: 13, capacidade: 4, estado: 'Livre' },
    { id: 14, capacidade: 4, estado: 'Livre' },
    { id: 15, capacidade: 4, estado: 'Livre' }
];

let clienteSelecionado = null;
let mesasSelecionadas = [];
let numPessoasAtual = 0;


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
        distribuicao.push(Math.min(pessoas, 4)); // Máximo 4 por mesa
    }
    
    return distribuicao;
}

// Atualizar a função que monitora mudanças no número de pessoas
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
    
    // Atualizar estado do botão
    atualizarBotaoOcupar();
}

function mostrarCalculoMesas(numPessoas) {
    const mesasNecessarias = calcularMesasNecessarias(numPessoas);
    const distribuicao = distribuirPessoas(numPessoas, mesasNecessarias);
    
    // Criar elemento de informação
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
    
    // Inserir antes do grid de mesas
    const mesasGrid = document.getElementById('mesas-grid');
    mesasGrid.parentNode.insertBefore(infoDiv, mesasGrid);
}

function selecionarMesa(mesaId) {
    if (numPessoasAtual === 0) {
        alert('Por favor, informe primeiro o número de pessoas.');
        return;
    }
    
    const mesasNecessarias = calcularMesasNecessarias(numPessoasAtual);
    const mesaCard = document.querySelector(`[onclick="selecionarMesa(${mesaId})"]`);
    
    // Se a mesa já está selecionada, desselecioná-la
    if (mesasSelecionadas.includes(mesaId)) {
        mesasSelecionadas = mesasSelecionadas.filter(id => id !== mesaId);
        mesaCard.classList.remove('selected', 'selected-multiple');
    } else {
        // Verificar se já temos o número necessário de mesas
        if (mesasSelecionadas.length >= mesasNecessarias) {
            alert(`Você já selecionou ${mesasNecessarias} mesa${mesasNecessarias > 1 ? 's' : ''}, que é o necessário para ${numPessoasAtual} pessoas.`);
            return;
        }
        
        // Adicionar mesa à seleção
        mesasSelecionadas.push(mesaId);
        
        if (mesasNecessarias === 1) {
            mesaCard.classList.add('selected');
        } else {
            mesaCard.classList.add('selected-multiple');
        }
    }
    
    atualizarBotaoOcupar();
}

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

function ocuparMesa() {
    if (mesasSelecionadas.length === 0) {
        alert('Por favor, selecione pelo menos uma mesa.');
        return;
    }
    
    const nomeCliente = document.getElementById('cliente-walkin').value.trim();
    const numPessoas = parseInt(document.getElementById('num-pessoas-walkin').value);
    const mesasNecessarias = calcularMesasNecessarias(numPessoas);
    
    if (mesasSelecionadas.length !== mesasNecessarias) {
        alert(`Para ${numPessoas} pessoas, você precisa selecionar exatamente ${mesasNecessarias} mesa${mesasNecessarias > 1 ? 's' : ''}.`);
        return;
    }
    
    const distribuicao = distribuirPessoas(numPessoas, mesasNecessarias);
    const horaAtual = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    
    // Ocupar todas as mesas selecionadas
    mesasSelecionadas.forEach((mesaId, index) => {
        const mesa = mesasSimuladas.find(m => m.id === mesaId);
        mesa.estado = 'Ocupada';
        mesa.cliente = nomeCliente || 'Cliente não informado';
        mesa.pessoas = distribuicao[index];
        mesa.totalPessoas = numPessoas; // Total do grupo
        mesa.mesasGrupo = mesasSelecionadas.length; // Quantas mesas o grupo ocupa
        mesa.horaOcupacao = horaAtual;
        mesa.grupoId = Date.now(); // ID único para o grupo
    });
    
    let mensagem = `Mesa${mesasSelecionadas.length > 1 ? 's' : ''} ${mesasSelecionadas.join(', ')} ocupada${mesasSelecionadas.length > 1 ? 's' : ''} com sucesso!\n\n`;
    mensagem += `Cliente: ${nomeCliente || 'Não informado'}\n`;
    mensagem += `Total de pessoas: ${numPessoas}\n`;
    mensagem += `Mesas ocupadas: ${mesasSelecionadas.length}\n`;
    
    if (mesasSelecionadas.length > 1) {
        mensagem += '\nDistribuição por mesa:\n';
        mesasSelecionadas.forEach((mesaId, index) => {
            mensagem += `Mesa ${mesaId}: ${distribuicao[index]} pessoa${distribuicao[index] > 1 ? 's' : ''}\n`;
        });
        mensagem += '\n💡 Lembre-se de unir as mesas no salão!';
    }
    
    alert(mensagem);
    
    // Resetar formulário
    cancelarOcupacao();
    
    // Atualizar listas
    carregarMesas();
    carregarMesasOcupadas();
}

function cancelarOcupacao() {
    document.getElementById('cliente-walkin').value = '';
    document.getElementById('num-pessoas-walkin').value = '';
    document.querySelectorAll('.mesa-card').forEach(card => {
        card.classList.remove('selected', 'selected-multiple');
    });
    mesasSelecionadas = [];
    numPessoasAtual = 0;
    
    // Remover aviso de cálculo
    const avisoExistente = document.querySelector('.mesas-calculadas');
    if (avisoExistente) {
        avisoExistente.remove();
    }
    
    atualizarBotaoOcupar();
}

function carregarMesas() {
    const mesasGrid = document.getElementById('mesas-grid');
    const mesasLivres = mesasSimuladas.filter(mesa => mesa.estado === 'Livre');
    
    mesasGrid.innerHTML = mesasLivres.map(mesa => `
        <div class="mesa-card" onclick="selecionarMesa(${mesa.id})">
            <h4>Mesa ${mesa.id}</h4>
            <p>Capacidade: ${mesa.capacidade}</p>
            <p>Disponível</p>
        </div>
    `).join('');
}

function carregarMesasOcupadas() {
    const mesasOcupadasLista = document.getElementById('mesas-ocupadas-lista');
    const mesasOcupadas = mesasSimuladas.filter(mesa => mesa.estado === 'Ocupada');
    
    if (mesasOcupadas.length === 0) {
        mesasOcupadasLista.innerHTML = '<p>Nenhuma mesa ocupada no momento.</p>';
        return;
    }
    
    // Agrupar mesas por grupo (mesmo cliente e mesmo horário)
    const grupos = {};
    mesasOcupadas.forEach(mesa => {
        const grupoId = mesa.grupoId || mesa.id;
        if (!grupos[grupoId]) {
            grupos[grupoId] = [];
        }
        grupos[grupoId].push(mesa);
    });
    
    let html = '';
    Object.values(grupos).forEach(grupo => {
        const mesaPrincipal = grupo[0];
        const totalPessoas = mesaPrincipal.totalPessoas || mesaPrincipal.pessoas;
        const mesasIds = grupo.map(m => m.id).join(', ');
        
        if (grupo.length > 1) {
            // Grupo de múltiplas mesas
            html += `
                <div class="mesa-ocupada-item">
                    <div class="mesa-ocupada-info">
                        <h4>Mesas ${mesasIds} (Grupo Unido - ${totalPessoas} pessoas)</h4>
                        <p><strong>Cliente:</strong> ${mesaPrincipal.cliente}</p>
                        <p><strong>Ocupadas desde:</strong> ${mesaPrincipal.horaOcupacao}</p>
                        <p><strong>Distribuição:</strong> ${grupo.map(m => `Mesa ${m.id} (${m.pessoas}p)`).join(', ')}</p>
                    </div>
                    <button class="liberar-btn" onclick="liberarGrupoMesas([${grupo.map(m => m.id).join(',')}])">Liberar Grupo</button>
                </div>
            `;
        } else {
            // Mesa individual
            const mesa = grupo[0];
            html += `
                <div class="mesa-ocupada-item">
                    <div class="mesa-ocupada-info">
                        <h4>Mesa ${mesa.id} (${mesa.pessoas} pessoas)</h4>
                        <p><strong>Cliente:</strong> ${mesa.cliente}</p>
                        <p><strong>Ocupada desde:</strong> ${mesa.horaOcupacao}</p>
                    </div>
                    <button class="liberar-btn" onclick="liberarMesa(${mesa.id})">Liberar Mesa</button>
                </div>
            `;
        }
    });
    
    mesasOcupadasLista.innerHTML = html;
}

function liberarGrupoMesas(mesasIds) {
    const nomeCliente = mesasSimuladas.find(m => mesasIds.includes(m.id))?.cliente;
    
    if (confirm(`Tem certeza que deseja liberar todas as mesas do grupo de ${nomeCliente}?\nMesas: ${mesasIds.join(', ')}`)) {
        mesasIds.forEach(mesaId => {
            const mesa = mesasSimuladas.find(m => m.id === mesaId);
            mesa.estado = 'Livre';
            delete mesa.cliente;
            delete mesa.pessoas;
            delete mesa.totalPessoas;
            delete mesa.mesasGrupo;
            delete mesa.horaOcupacao;
            delete mesa.grupoId;
        });
        
        alert(`Grupo de mesas ${mesasIds.join(', ')} liberado com sucesso!`);
        
        carregarMesas();
        carregarMesasOcupadas();
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

// Resto das funções permanecem iguais...
// [Incluir aqui todas as outras funções que não foram modificadas]