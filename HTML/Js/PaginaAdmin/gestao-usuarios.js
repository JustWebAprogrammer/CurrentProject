let usuarioEditando = null;

document.addEventListener('DOMContentLoaded', function() {
    carregarClientes();
    carregarUsuariosSistema();
    verificarMensagens();
});

function verificarMensagens() {
    const urlParams = new URLSearchParams(window.location.search);
    const erro = urlParams.get('erro');
    const sucesso = urlParams.get('sucesso');
    
    if (erro) {
        alert('Erro: ' + erro);
    }
    
    if (sucesso) {
        alert('Sucesso: ' + sucesso);
    }
}

function mostrarTab(tab) {
    // Esconder todas as tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remover active de todos os botões
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar tab selecionada
    document.getElementById(`tab-${tab}`).classList.add('active');
    event.target.classList.add('active');
    
    // Carregar dados da tab
    if (tab === 'clientes') {
        carregarClientes();
    } else if (tab === 'sistema') {
        carregarUsuariosSistema();
    }
}

function carregarClientes(busca = '') {
    const url = busca ? 
        `BackEnd/api/admin-usuarios.php?acao=clientes&busca=${encodeURIComponent(busca)}` :
        'BackEnd/api/admin-usuarios.php?acao=clientes';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                console.error('Erro:', data.erro);
                return;
            }
            
            const container = document.getElementById('lista-clientes');
            container.innerHTML = '';
            
            if (data.length === 0) {
                container.innerHTML = '<div class="lista-vazia">Nenhum cliente encontrado.</div>';
                return;
            }
            
            data.forEach(cliente => {
                const clienteDiv = document.createElement('div');
                clienteDiv.className = 'usuario-item';
                clienteDiv.innerHTML = `
                    <div class="usuario-info">
                        <h4>${cliente.nome}</h4>
                        <p><strong>Email:</strong> ${cliente.email}</p>
                        <p><strong>Telefone:</strong> ${cliente.telemovel}</p>
                        <p><strong>Total de Reservas:</strong> ${cliente.total_reservas || 0}</p>
                        <p><strong>Última Reserva:</strong> ${cliente.ultima_reserva ? new Date(cliente.ultima_reserva).toLocaleDateString() : 'Nunca'}</p>
                        <span class="usuario-badge badge-cliente">Cliente</span>
                    </div>
                    <div class="usuario-acoes">
                        <button class="btn-detalhes" onclick="verDetalhesCliente(${cliente.id})">Detalhes</button>
                        <button class="btn-remover" onclick="apagarCliente(${cliente.id}, '${cliente.nome}')">Apagar</button>
                    </div>
                `;
                container.appendChild(clienteDiv);
            });
        })
        .catch(error => {
            console.error('Erro ao carregar clientes:', error);
        });
}

function carregarUsuariosSistema() {
    fetch('BackEnd/api/admin-usuarios.php?acao=usuarios_sistema')
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                console.error('Erro:', data.erro);
                return;
            }
            
            const container = document.getElementById('lista-sistema');
            container.innerHTML = '';
            
            if (data.length === 0) {
                container.innerHTML = '<div class="lista-vazia">Nenhum usuário do sistema encontrado.</div>';
                return;
            }
            
            data.forEach(usuario => {
                const usuarioDiv = document.createElement('div');
                usuarioDiv.className = 'usuario-item';
                usuarioDiv.innerHTML = `
                    <div class="usuario-info">
                        <h4>${usuario.nome}</h4>
                        <p><strong>Email:</strong> ${usuario.email}</p>
                        <p><strong>Cadastrado em:</strong> ${new Date(usuario.data_criacao).toLocaleDateString()}</p>
                        <span class="usuario-badge badge-${usuario.tipo.toLowerCase()}">${usuario.tipo}</span>
                    </div>
                    <div class="usuario-acoes">
                        <button class="btn-editar" onclick="editarUsuario(${usuario.id}, '${usuario.nome}', '${usuario.email}', '${usuario.tipo}')">Editar</button>
                        <button class="btn-remover" onclick="removerUsuario(${usuario.id})">Remover</button>
                    </div>
                `;
                container.appendChild(usuarioDiv);
            });
        })
        .catch(error => {
            console.error('Erro ao carregar usuários:', error);
        });
}

function buscarClientes() {
    const busca = document.getElementById('buscar-cliente').value.trim();
    carregarClientes(busca);
}

function abrirModalUsuario() {
    usuarioEditando = null;
    document.getElementById('modal-titulo').textContent = 'Adicionar Usuário';
    document.getElementById('form-usuario').reset();
    document.getElementById('senha-usuario').required = true;
    document.getElementById('modal-usuario').style.display = 'block';
}

function editarUsuario(id, nome, email, tipo) {
    usuarioEditando = id;
    document.getElementById('modal-titulo').textContent = 'Editar Usuário';
    document.getElementById('nome-usuario').value = nome;
    document.getElementById('email-usuario').value = email;
    document.getElementById('tipo-usuario').value = tipo;
    document.getElementById('senha-usuario').value = '';
    document.getElementById('senha-usuario').required = false;
    document.getElementById('modal-usuario').style.display = 'block';
}

function fecharModalUsuario() {
    document.getElementById('modal-usuario').style.display = 'none';
    usuarioEditando = null;
}

function salvarUsuario() {
    const nome = document.getElementById('nome-usuario').value.trim();
    const email = document.getElementById('email-usuario').value.trim();
    const senha = document.getElementById('senha-usuario').value.trim();
    const tipo = document.getElementById('tipo-usuario').value;
    
    if (!nome || !email || !tipo) {
        alert('Por favor, preencha todos os campos obrigatórios.');
        return;
    }
    
    if (usuarioEditando === null && !senha) {
        alert('A senha é obrigatória para novos usuários.');
        return;
    }
    
    const formData = new FormData();
    formData.append('nome', nome);
    formData.append('email', email);
    formData.append('senha', senha);
    formData.append('tipo', tipo);
    
    if (usuarioEditando !== null) {
        formData.append('acao', 'editar_usuario');
        formData.append('id', usuarioEditando);
    } else {
        formData.append('acao', 'adicionar_usuario');
    }
    
    fetch('BackEnd/api/admin-usuarios.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        window.location.reload();
    })
    .catch(error => {
        console.error('Erro ao salvar usuário:', error);
        alert('Erro ao salvar usuário');
    });
}

function removerUsuario(id) {
    if (!confirm('Tem certeza que deseja remover este usuário?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'remover_usuario');
    formData.append('usuario_id', id);
    
    fetch('BackEnd/api/admin-usuarios.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        window.location.reload();
    })
    .catch(error => {
        console.error('Erro ao remover usuário:', error);
        alert('Erro ao remover usuário');
    });
}

function verDetalhesCliente(clienteId) {
    // Buscar detalhes completos do cliente
    fetch(`BackEnd/api/admin-usuarios.php?acao=detalhes_cliente&cliente_id=${clienteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert('Erro ao carregar detalhes do cliente: ' + data.erro);
                return;
            }
            
            // Preencher o modal com os dados do cliente
            const cliente = data.cliente;
            const reservas = data.reservas || [];
            
            document.getElementById('detalhe-nome').textContent = cliente.nome;
            document.getElementById('detalhe-email').textContent = cliente.email;
            document.getElementById('detalhe-telefone').textContent = cliente.telemovel;
            document.getElementById('detalhe-data-cadastro').textContent = new Date(cliente.data_criacao).toLocaleDateString();
            document.getElementById('detalhe-total-reservas').textContent = reservas.length;
            
            // Mostrar histórico de reservas
            const historicoContainer = document.getElementById('historico-reservas');
            historicoContainer.innerHTML = '';
            
            if (reservas.length === 0) {
                historicoContainer.innerHTML = '<p>Nenhuma reserva encontrada.</p>';
            } else {
                reservas.forEach(reserva => {
                    const reservaDiv = document.createElement('div');
                    reservaDiv.className = 'reserva-historico';
                    
                    const statusClass = getStatusClassDetalhes(reserva.status);
                    const dataFormatada = new Date(reserva.data).toLocaleDateString();
                    
                    reservaDiv.innerHTML = `
                        <div class="reserva-info-detalhes">
                            <p><strong>Data:</strong> ${dataFormatada} às ${reserva.hora}</p>
                            <p><strong>Mesa:</strong> ${reserva.mesa_id || 'N/A'} | <strong>Pessoas:</strong> ${reserva.num_pessoas}</p>
                            <span class="status-badge ${statusClass}">${reserva.status}</span>
                        </div>
                    `;
                    historicoContainer.appendChild(reservaDiv);
                });
            }
            
            // Mostrar o modal
            document.getElementById('modal-cliente').style.display = 'block';
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do cliente:', error);
            alert('Erro ao carregar detalhes do cliente');
        });
}

function getStatusClassDetalhes(status) {
    switch(status) {
        case 'Reservado': return 'status-reservado';
        case 'Concluído': return 'status-concluido';
        case 'Cancelado': return 'status-cancelado';
        case 'Expirado': return 'status-expirado';
        default: return '';
    }
}

function apagarCliente(clienteId, nomeCliente) {
    if (!confirm(`Tem certeza que deseja apagar o cliente "${nomeCliente}"?\n\nAtenção: Esta ação irá:\n- Apagar permanentemente o cliente\n- Cancelar todas as reservas ativas\n- Esta ação não pode ser desfeita!`)) {
        return;
    }
    
    // Segunda confirmação para ações críticas
    if (!confirm(`CONFIRMAÇÃO FINAL:\n\nVocê está prestes a apagar PERMANENTEMENTE o cliente "${nomeCliente}" e todas as suas reservas.\n\nEsta ação é IRREVERSÍVEL!\n\nDeseja continuar?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'apagar_cliente');
    formData.append('cliente_id', clienteId);
    
    fetch('BackEnd/api/admin-usuarios.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Como a resposta redireciona, aguardar e recarregar
        setTimeout(() => {
            window.location.reload();
        }, 100);
    })
    .catch(error => {
        console.error('Erro ao apagar cliente:', error);
        alert('Erro ao apagar cliente. Tente novamente.');
    });
}

function fecharModalCliente() {
    document.getElementById('modal-cliente').style.display = 'none';
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    const modalUsuario = document.getElementById('modal-usuario');
    const modalCliente = document.getElementById('modal-cliente');
    
    if (event.target === modalUsuario) {
        modalUsuario.style.display = 'none';
    }
    
    if (event.target === modalCliente) {
        modalCliente.style.display = 'none';
    }
}

// Buscar clientes ao pressionar Enter
document.getElementById('buscar-cliente').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        buscarClientes();
    }
});