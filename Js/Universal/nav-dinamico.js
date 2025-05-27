// Função para verificar status de login e atualizar navegação
async function atualizarNavegacao() {
    try {
        const response = await fetch('BackEnd/verificar_status_sessao.php');
        const data = await response.json();
        
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;
        
        // Limpar botões existentes
        navbar.innerHTML = '';
        
        if (data.logado) {
            // Usuário logado
            navbar.innerHTML = `
                <button onclick="navigateTo('Perfil.php')">Suas Reservas</button>
                <button onclick="navigateTo('PaginaIncial.html')">Início</button>
                <button onclick="navigateTo('BackEnd/api/logoff.php')">Sair</button>
            `;
        } else {
            // Usuário não logado
            navbar.innerHTML = `
                <button onclick="navigateTo('Login.html')">Suas Reservas</button>
                <button onclick="navigateTo('PaginaIncial.html')">Início</button>
                <button onclick="navigateTo('Login.html')">Entrar</button>
            `;
        }
    } catch (error) {
        console.error('Erro ao verificar status de login:', error);
        // Em caso de erro, mostrar navegação não logada
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.innerHTML = `
                <button onclick="navigateTo('Login.html')">Suas Reservas</button>
                <button onclick="navigateTo('PaginaIncial.html')">Início</button>
                <button onclick="navigateTo('Login.html')">Entrar</button>
            `;
        }
    }
}

// Função para navegação
function navigateTo(url) {
    window.location.href = url;
}

// Executar quando a página carregar
document.addEventListener('DOMContentLoaded', atualizarNavegacao);