<?php
class UsuarioSistema {
    private $conn;
    private $table_name = "usuarios_sistema";

    public $id;
    public $nome;
    public $email;
    public $senha;
    public $tipo;
    public $data_criacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar novo usuário do sistema
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha=:senha, tipo=:tipo";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        
        // Hash da senha
        $senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);

        // Bind dos valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha", $senha_hash);
        $stmt->bindParam(":tipo", $this->tipo);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Ler todos os usuários do sistema
    public function lerTodos() {
        $query = "SELECT id, nome, email, tipo, data_criacao FROM " . $this->table_name . " ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Ler usuário específico
    public function lerUm() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->senha = $row['senha'];
            $this->tipo = $row['tipo'];
            $this->data_criacao = $row['data_criacao'];
            return true;
        }

        return false;
    }

    // Buscar usuário por email
    public function buscarPorEmail() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->senha = $row['senha'];
            $this->tipo = $row['tipo'];
            $this->data_criacao = $row['data_criacao'];
            return true;
        }

        return false;
    }

    // Atualizar usuário
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome = :nome, email = :email, tipo = :tipo";
        
        // Se uma nova senha foi fornecida, incluir no update
        if (!empty($this->senha)) {
            $query .= ", senha = :senha";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind dos valores
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':tipo', $this->tipo);
        $stmt->bindParam(':id', $this->id);

        // Se nova senha foi fornecida
        if (!empty($this->senha)) {
            $senha_hash = password_hash($this->senha, PASSWORD_DEFAULT);
            $stmt->bindParam(':senha', $senha_hash);
        }

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Deletar usuário
    public function deletar() {
        // Verificar se não é o último administrador
        if ($this->tipo === 'Administrador') {
            $query_check = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE tipo = 'Administrador'";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->execute();
            $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] <= 1) {
                return false; // Não pode deletar o último administrador
            }
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Verificar login
    public function verificarLogin($email, $senha) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($senha, $row['senha'])) {
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->tipo = $row['tipo'];
            $this->data_criacao = $row['data_criacao'];
            return true;
        }

        return false;
    }

    // Verificar se email já existe (para outro usuário)
    public function emailExiste($email, $id_excluir = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        
        if ($id_excluir !== null) {
            $query .= " AND id != ?";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        
        if ($id_excluir !== null) {
            $stmt->bindParam(2, $id_excluir);
        }
        
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Contar usuários por tipo
    public function contarPorTipo($tipo) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE tipo = :tipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obter estatísticas dos usuários
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN tipo = 'Administrador' THEN 1 ELSE 0 END) as administradores,
                    SUM(CASE WHEN tipo = 'Rececionista' THEN 1 ELSE 0 END) as rececionistas
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar usuários
    public function buscar($termo_busca) {
        $query = "SELECT id, nome, email, tipo, data_criacao FROM " . $this->table_name . " 
                  WHERE nome LIKE :termo OR email LIKE :termo 
                  ORDER BY nome ASC";

        $stmt = $this->conn->prepare($query);
        $termo_busca = "%" . htmlspecialchars(strip_tags($termo_busca)) . "%";
        $stmt->bindParam(':termo', $termo_busca);
        $stmt->execute();

        return $stmt;
    }

    // Alterar senha
    public function alterarSenha($nova_senha) {
        $query = "UPDATE " . $this->table_name . " SET senha = :senha WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Validar dados do usuário
    public function validar() {
        $erros = [];

        if (empty($this->nome)) {
            $erros[] = "Nome é obrigatório";
        }

        if (strlen($this->nome) < 2) {
            $erros[] = "Nome deve ter pelo menos 2 caracteres";
        }

        if (empty($this->email)) {
            $erros[] = "Email é obrigatório";
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Email inválido";
        }

        if (!in_array($this->tipo, ['Administrador', 'Rececionista'])) {
            $erros[] = "Tipo deve ser 'Administrador' ou 'Rececionista'";
        }

        // Validar senha apenas se for um novo usuário ou se a senha foi alterada
        if (!empty($this->senha)) {
            if (strlen($this->senha) < 6) {
                $erros[] = "Senha deve ter pelo menos 6 caracteres";
            }
        }

        return $erros;
    }

    // Verificar se é administrador
    public function isAdmin() {
        return $this->tipo === 'Administrador';
    }

    // Verificar se é rececionista
    public function isRececionista() {
        return $this->tipo === 'Rececionista';
    }

    // Obter último login (se implementar tabela de logs)
    public function obterUltimoLogin() {
        // Esta funcionalidade requer uma tabela de logs de login
        // Por enquanto, retorna null
        return null;
    }
}
?>