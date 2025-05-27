// Verificar mensagens na URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const erro = urlParams.get('erro');
    const sucesso = urlParams.get('sucesso');
    
    if (erro) {
        mostrarMensagem(erro, 'erro');
    }
    
    if (sucesso) {
        mostrarMensagem(sucesso, 'sucesso');
    }
});

function mostrarMensagem(texto, tipo) {
    const container = document.getElementById('mensagem-container');
    const mensagem = document.getElementById('mensagem');
    
    mensagem.textContent = texto;
    mensagem.className = `mensagem ${tipo}`;
    container.style.display = 'block';
    
    // Esconder mensagem após 5 segundos
    setTimeout(() => {
        container.style.display = 'none';
    }, 5000);
}

// Validação do formulário
document.querySelector('.login-form').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value.trim();
    
    if (!email || !senha) {
        e.preventDefault();
        mostrarMensagem('Por favor, preencha todos os campos.', 'erro');
        return;
    }
    
    if (!isValidEmail(email)) {
        e.preventDefault();
        mostrarMensagem('Por favor, insira um email válido.', 'erro');
        return;
    }
});

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}