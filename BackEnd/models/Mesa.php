<?php
class Mesa {
    private $conn;
    private $table_name = "mesas";

    public $id;
    public $capacidade;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar nova mesa
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET capacidade=:capacidade, estado=:estado";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->capacidade = htmlspecialchars(strip_tags($this->capacidade));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        // Bind dos valores
        $stmt->bindParam(":capacidade", $this->capacidade);
        $stmt->bindParam(":estado", $this->estado);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Ler todas as mesas
    public function lerTodas() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Ler mesa específica
    public function lerUma() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->capacidade = $row['capacidade'];
            $this->estado = $row['estado'];
            return true;
        }

        return false;
    }

    // Atualizar mesa
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET capacidade = :capacidade, estado = :estado 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->capacidade = htmlspecialchars(strip_tags($this->capacidade));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind dos valores
        $stmt->bindParam(':capacidade', $this->capacidade);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Deletar mesa
    public function deletar() {
        // Verificar se a mesa não tem reservas ativas
        $query_check = "SELECT COUNT(*) as total FROM reservas 
                        WHERE mesa_id = :id AND status = 'Reservado' AND data >= CURDATE()";
        
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(':id', $this->id);
        $stmt_check->execute();
        
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if($result['total'] > 0) {
            return false; // Não pode deletar mesa com reservas ativas
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Verificar disponibilidade da mesa
    public function verificarDisponibilidade($data, $hora) {
        $query = "SELECT COUNT(*) as conflitos FROM reservas 
                  WHERE mesa_id = :mesa_id AND data = :data AND hora = :hora AND status = 'Reservado'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mesa_id', $this->id);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora', $hora);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['conflitos'] == 0;
    }

    // Obter mesas disponíveis para uma data/hora/capacidade específica
    public function obterMesasDisponiveis($data, $hora, $num_pessoas) {
        $query = "SELECT m.* FROM " . $this->table_name . " m 
                  WHERE m.capacidade >= :num_pessoas 
                  AND m.id NOT IN (
                      SELECT r.mesa_id FROM reservas r 
                      WHERE r.data = :data AND r.hora = :hora AND r.status = 'Reservado'
                  )
                  ORDER BY m.capacidade ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':num_pessoas', $num_pessoas);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':hora', $hora);
        $stmt->execute();

        return $stmt;
    }

    // Alterar estado da mesa (Livre/Ocupada)
    public function alterarEstado($novoEstado) {
        $query = "UPDATE " . $this->table_name . " SET estado = :estado WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $novoEstado);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            $this->estado = $novoEstado;
            return true;
        }

        return false;
    }

    // Contar total de mesas
    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Contar mesas por estado
    public function contarPorEstado($estado) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE estado = :estado";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':estado', $estado);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Obter estatísticas das mesas
    public function obterEstatisticas() {
        $query = "SELECT 
                    COUNT(*) as total_mesas,
                    SUM(CASE WHEN estado = 'Livre' THEN 1 ELSE 0 END) as mesas_livres,
                    SUM(CASE WHEN estado = 'Ocupada' THEN 1 ELSE 0 END) as mesas_ocupadas,
                    AVG(capacidade) as capacidade_media,
                    MAX(capacidade) as maior_capacidade,
                    MIN(capacidade) as menor_capacidade
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Validar dados da mesa
    public function validar() {
        $erros = [];

        if (empty($this->capacidade) || $this->capacidade < 1) {
            $erros[] = "Capacidade deve ser maior que 0";
        }

        if ($this->capacidade > 60) {
            $erros[] = "Capacidade não pode ser maior que 60";
        }

        if (!in_array($this->estado, ['Livre', 'Ocupada'])) {
            $erros[] = "Estado deve ser 'Livre' ou 'Ocupada'";
        }

        return $erros;
    }
}
?>