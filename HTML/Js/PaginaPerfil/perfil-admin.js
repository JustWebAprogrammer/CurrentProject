document.addEventListener('DOMContentLoaded', function() {
    carregarResumoSistema();
});

let camposEditando = [];

function toggleEdit(fieldId) {
    const field = document.getElementById(fieldId);
    const editBtn = field.nextElementSibling;
    const formActions = document.querySelector('.form-actions');
    
    if (field.readOnly) {
        // Habilitar edição
        field.readOnly = false;
        field.focus();
        field.select();
        editBtn.textContent = '✅';
        editBtn.style.background = '#4CAF50';
        
        if (!camposEditando.includes(fieldId)) {
            camposEditando.push(fieldId);
        }
        
        formActions.style.display = 'block';
    } else {
        // Cancelar edição deste campo
        field.readOnly = true;
        editBtn.textContent = '✏️';
        editBtn.style.background = '';
        
        // Restaurar valor original
        const valorOriginal = usuarioLogado.dados[fieldId.replace('-', '_')] || usuarioLogado.dados[fieldId.replace('nome-usuario', 'nome')];
        field.value = valorOriginal;
        
        camposEditando = camposEditando.filter(id => id !== fieldId);
        
        if (camposEditando.length === 0) {
            formActions.style.display = 'none';
        }
    }
}

function cancelEdit() {
    // Restaurar todos os campos
    camposEditando.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        const editBtn = field.nextElementSibling;
        
        field.readOnly = true;
        editBtn.textContent = '✏️';
        editBtn.style.background = '';
        
        // Restaurar valor original
        const valorOriginal = usuarioLogado.dados[fieldId.replace('-', '_')] || usuarioLogado.dados[fieldId.replace('nome-usuario', 'nome')];
        field.value = valorOriginal;
    });
    
    camposEditando = [];
    document.querySelector('.form-actions').style.display = 'none';
}

function carregarResumoSistema() {
    // Carregar estatísticas do sistema
    fetch('BackEnd/api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('reservas-hoje').textContent = data.reservas_hoje || '0';
                document.getElementById('total-clientes').textContent = data.total_clientes || '0';
                document.getElementById('mesas-ocupadas').textContent = data.mesas_ocupadas || '0';
                document.getElementById('taxa-ocupacao').textContent = (data.taxa_ocupacao || '0') + '%';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar resumo:', error);
        });
}

// Interceptar submissão do formulário
document.getElementById('perfil-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (camposEditando.length === 0) {
        alert('Nenhuma alteração foi feita.');
        return;
    }
    
    // Confirmar alterações se for admin
    if (usuarioLogado.dados.tipo === 'Administrador') {
        if (!confirm('Confirma as alterações nos dados do administrador?')) {
            return;
        }
    }
    
    // Enviar formulário
    this.submit();
});