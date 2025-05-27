<?php

// Verificar se a sessão já não está ativa antes de iniciar
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'BackEnd/verificar_sessao.php';

// Verificar se o usuário está logado
if (!verificarLogin()) {
    header("Location: Login.html");
    exit;
}

$cliente = obterClienteLogado();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Lar Da Dona Xinga</title>
    <link rel="stylesheet" href="Css/Perfil/perfil.css">
    <link rel="stylesheet" href="Css/Universal/nav.css"/>
    <link rel="stylesheet" href="Css/Universal/footer.css">
</head>
<body>

<!-- Navegação -->
<nav>
    <div class="Logotipo">
        <img src="Fotos/Icondesite.png" alt="Logotipo do Site">
    </div>
    <div class="navbar">
    <button onclick="navigateTo('Reserva.html')">Fazer Reserva</button>
        <button onclick="navigateTo('PaginaIncial.html')">Início</button>
        <button onclick="navigateTo('BackEnd/api/logoff.php')">Sair</button>
    </div>
</nav>

<!-- Conteúdo Principal -->
<main>
    <div class="perfil-container">
        <h1>Meu Perfil</h1>
        
        <!-- Mostrar mensagem de sucesso se houver -->
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($_GET['sucesso']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Informações do Usuário -->
        <section class="user-info">
            <h2>Informações Pessoais</h2>
            <form id="perfil-form" action="BackEnd/api/perfil.php" method="POST">
                <div class="form-group">
                    <label for="nome-usuario">Nome de Usuário</label>
                    <input type="text" id="nome-usuario" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" readonly>
                    <button type="button" class="edit-btn" onclick="toggleEdit('nome-usuario')">✏️</button>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" readonly>
                    <button type="button" class="edit-btn" onclick="toggleEdit('email')">✏️</button>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="text" id="telefone" name="telemovel" value="<?php echo htmlspecialchars($cliente['telemovel']); ?>" readonly>
                    <button type="button" class="edit-btn" onclick="toggleEdit('telefone')">✏️</button>
                </div>
                
                <div class="form-actions" style="display: none;">
                    <button type="submit" id="salvar-btn">Salvar Alterações</button>
                    <button type="button" id="cancelar-btn" onclick="cancelEdit()">Cancelar</button>
                </div>
            </form>
        </section>
        
        <!-- Histórico de Reservas -->
        <section class="reservas-section">
            <h2>Minhas Reservas</h2>
            
            <div class="historico-filtros">
                <button class="historico-btn active" onclick="filterReservas('proximas')">Próximas</button>
                <button class="historico-btn" onclick="filterReservas('passadas')">Passadas</button>
                <button class="historico-btn" onclick="filterReservas('canceladas')">Canceladas</button>
            </div>
            
            <div id="reservas-lista" class="reservas-lista">
                <!-- Reservas serão carregadas aqui -->
            </div>
        </section>
    </div>
</main>

<!-- Rodapé -->
<footer class="footer-section">
    <div class="footer-container">
        <div class="footer-logo">
            <img src="Fotos/Icondesite.png" alt="Logo do Restaurante">
            <p>Lar Da Dona Xinga</p>
        </div>
        
        <div class="footer-help">
            <h3>Precisa de ajuda com sua reserva?</h3>
            <p>Entre em contato conosco: <a href="mailto:Tomaszinho19@gmail.com">contato@restaurante.com</a></p>
            <p>Telefone: (+244) 942-761-755</p>
        </div>
        <div class="footer-links">
            <h3>Dúvidas Frequentes</h3>
            <ul>
                <li><a href="faq.html">Perguntas Frequentes</a></li>
                <li><a href="termos.html">Termos de Uso e Política de Privacidade</a></li>
                <li><a href="contato.html">Fale Conosco</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 Lar Da Dona Xinga. Todos os direitos reservados.</p>
    </div>
</footer>

<script>
// Dados do cliente passados do PHP para JavaScript
const clienteLogado = {
    id: <?php echo $cliente['id']; ?>,
    nome: "<?php echo htmlspecialchars($cliente['nome']); ?>",
    email: "<?php echo htmlspecialchars($cliente['email']); ?>",
    telemovel: "<?php echo htmlspecialchars($cliente['telemovel']); ?>"
};
</script>
<script src="Js/PaginaPerfil/perfil-session.js"></script>
<script>
function navigateTo(url) {
    window.location.href = url;
}
</script>

</body>
</html>