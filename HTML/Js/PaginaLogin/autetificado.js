function validateLoginForm(event) {
    // Limpar mensagem de erro anterior
    const errorMessage = document.getElementById('error-message');
    errorMessage.textContent = '';

    // Obter valores dos campos
    const email = document.getElementById('email').value.trim();
    const senha = document.getElementById('senha').value.trim();

    // Validar E-mail
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        errorMessage.textContent = 'Por favor, insira um e-mail válido.';
        event.preventDefault();
        return false;
    }

    // Validar Senha
    if (senha.length < 6) {
        errorMessage.textContent = 'A senha deve ter pelo menos 6 caracteres.';
        event.preventDefault();
        return false;
    }

    // Mostrar mensagem de carregamento
    const submitBtn = document.querySelector('button[type="submit"]');
    submitBtn.textContent = 'Entrando...';
    submitBtn.disabled = true;

    // Permitir envio do formulário (não impedir)
    return true;
}

// Verificar se há mensagem de erro na URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const erro = urlParams.get('erro');
    
    if (erro) {
        const errorMessage = document.getElementById('error-message');
        errorMessage.textContent = decodeURIComponent(erro);
        errorMessage.style.color = 'red';
    }
});