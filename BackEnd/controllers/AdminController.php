<?php
require_once '../models/Cliente.php';
require_once '../models/Reserva.php';
require_once '../models/Mesa.php';
require_once '../models/UsuarioSistema.php';

class AdminController {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function login($email, $senha) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios_sistema WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            return [
                'sucesso' => false,
                'erro' => 'Email ou senha incorretos'
            ];
        }

        return [
            'sucesso' => true,
            'usuario' => [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'tipo' => $usuario['tipo']
            ]
        ];
    }

    public function obterEstatisticas() {
        // Total de clientes
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM clientes");
        $totalClientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Reservas hoje
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE DATE(data) = CURDATE() AND status = 'Reservado'");
        $stmt->execute();
        $reservasHoje = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Mesas ocupadas
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM mesas WHERE estado = 'Ocupada'");
        $mesasOcupadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total de mesas
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM mesas");
        $totalMesas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $taxaOcupacao = $totalMesas > 0 ? round(($mesasOcupadas / $totalMesas) * 100, 2) : 0;

        return [
            'totalClientes' => $totalClientes,
            'reservasHoje' => $reservasHoje,
            'mesasOcupadas' => $mesasOcupadas,
            'taxaOcupacao' => $taxaOcupacao
        ];
    }

    public function obterMesas() {
        $stmt = $this->db->query("SELECT * FROM mesas ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obterDetalhesCliente($cliente_id) {
        try {
            // Buscar dados do cliente
            $query_cliente = "SELECT * FROM clientes WHERE id = :id";
            $stmt_cliente = $this->db->prepare($query_cliente);
            $stmt_cliente->bindParam(":id", $cliente_id);
            $stmt_cliente->execute();
            
            $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                return ['erro' => 'Cliente não encontrado'];
            }
            
            // Buscar reservas do cliente
            $query_reservas = "SELECT * FROM reservas 
                              WHERE cliente_id = :cliente_id 
                              AND num_pessoas > 0 
                              ORDER BY data DESC, hora DESC";
            $stmt_reservas = $this->db->prepare($query_reservas);
            $stmt_reservas->bindParam(":cliente_id", $cliente_id);
            $stmt_reservas->execute();
            
            $reservas = $stmt_reservas->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'cliente' => $cliente,
                'reservas' => $reservas
            ];
            
        } catch (Exception $e) {
            return ['erro' => 'Erro interno do servidor'];
        }
    }
    
    public function apagarCliente($cliente_id) {
        try {
            error_log("Iniciando processo de apagar cliente ID: " . $cliente_id);
            
            $this->db->beginTransaction();
            
            // Verificar se o cliente existe
            $query_verificar = "SELECT id, nome FROM clientes WHERE id = :id";
            $stmt_verificar = $this->db->prepare($query_verificar);
            $stmt_verificar->bindParam(":id", $cliente_id, PDO::PARAM_INT);
            $stmt_verificar->execute();
            
            $cliente = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
            if (!$cliente) {
                $this->db->rollback();
                return [
                    'sucesso' => false,
                    'erro' => 'Cliente não encontrado'
                ];
            }
            
            error_log("Cliente encontrado: " . $cliente['nome']);
            
            // PASSO 1: Primeiro, apagar reservas "filhas" (que referenciam outras reservas)
            $query_filhas = "DELETE FROM reservas 
                            WHERE cliente_id = :cliente_id 
                            AND reserva_principal_id IS NOT NULL";
            $stmt_filhas = $this->db->prepare($query_filhas);
            $stmt_filhas->bindParam(":cliente_id", $cliente_id, PDO::PARAM_INT);
            
            if (!$stmt_filhas->execute()) {
                $this->db->rollback();
                error_log("Erro ao apagar reservas filhas do cliente: " . $cliente_id);
                return [
                    'sucesso' => false,
                    'erro' => 'Erro ao apagar reservas secundárias do cliente'
                ];
            }
            
            $reservas_filhas_apagadas = $stmt_filhas->rowCount();
            error_log("Reservas filhas apagadas: " . $reservas_filhas_apagadas);
            
            // PASSO 2: Depois, apagar reservas principais (que não referenciam outras)
            $query_principais = "DELETE FROM reservas 
                               WHERE cliente_id = :cliente_id 
                               AND reserva_principal_id IS NULL";
            $stmt_principais = $this->db->prepare($query_principais);
            $stmt_principais->bindParam(":cliente_id", $cliente_id, PDO::PARAM_INT);
            
            if (!$stmt_principais->execute()) {
                $this->db->rollback();
                error_log("Erro ao apagar reservas principais do cliente: " . $cliente_id);
                return [
                    'sucesso' => false,
                    'erro' => 'Erro ao apagar reservas principais do cliente'
                ];
            }
            
            $reservas_principais_apagadas = $stmt_principais->rowCount();
            error_log("Reservas principais apagadas: " . $reservas_principais_apagadas);
            
            // PASSO 3: Verificar se ainda restam reservas (caso haja alguma inconsistência)
            $query_verificar_reservas = "SELECT COUNT(*) as total FROM reservas WHERE cliente_id = :cliente_id";
            $stmt_verificar_reservas = $this->db->prepare($query_verificar_reservas);
            $stmt_verificar_reservas->bindParam(":cliente_id", $cliente_id, PDO::PARAM_INT);
            $stmt_verificar_reservas->execute();
            $resultado = $stmt_verificar_reservas->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                $this->db->rollback();
                error_log("Ainda restam " . $resultado['total'] . " reservas para o cliente: " . $cliente_id);
                return [
                    'sucesso' => false,
                    'erro' => 'Não foi possível apagar todas as reservas do cliente'
                ];
            }
            
            // PASSO 4: Agora apagar o cliente
            $query_cliente = "DELETE FROM clientes WHERE id = :id";
            $stmt_cliente = $this->db->prepare($query_cliente);
            $stmt_cliente->bindParam(":id", $cliente_id, PDO::PARAM_INT);
            
            if ($stmt_cliente->execute() && $stmt_cliente->rowCount() > 0) {
                $this->db->commit();
                error_log("Cliente apagado com sucesso: " . $cliente_id);
                $total_reservas = $reservas_filhas_apagadas + $reservas_principais_apagadas;
                return [
                    'sucesso' => true,
                    'mensagem' => "Cliente removido com sucesso! (Foram removidas {$total_reservas} reservas)"
                ];
            } else {
                $this->db->rollback();
                error_log("Erro ao apagar cliente: " . $cliente_id);
                return [
                    'sucesso' => false,
                    'erro' => 'Erro ao remover cliente'
                ];
            }
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            error_log("Exceção em apagarCliente: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'sucesso' => false,
                'erro' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }

    
    public function adicionarMesa($capacidade) {
        try {
            $stmt = $this->db->prepare("INSERT INTO mesas (capacidade, estado) VALUES (?, 'Livre')");
            $stmt->execute([$capacidade]);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Mesa adicionada com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao adicionar mesa: ' . $e->getMessage()
            ];
        }
    }

    public function removerMesa($mesaId) {
        try {
            // Verificar se a mesa está ocupada
            $stmt = $this->db->prepare("SELECT estado FROM mesas WHERE id = ?");
            $stmt->execute([$mesaId]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mesa) {
                return [
                    'sucesso' => false,
                    'erro' => 'Mesa não encontrada'
                ];
            }

            if ($mesa['estado'] === 'Ocupada') {
                return [
                    'sucesso' => false,
                    'erro' => 'Não é possível remover uma mesa ocupada'
                ];
            }

            // Verificar se há reservas futuras para esta mesa
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE mesa_id = ? AND data >= CURDATE() AND status = 'Reservado'");
            $stmt->execute([$mesaId]);
            $reservasFuturas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            if ($reservasFuturas > 0) {
                return [
                    'sucesso' => false,
                    'erro' => 'Não é possível remover uma mesa com reservas futuras'
                ];
            }

            $stmt = $this->db->prepare("DELETE FROM mesas WHERE id = ?");
            $stmt->execute([$mesaId]);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Mesa removida com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao remover mesa: ' . $e->getMessage()
            ];
        }
    }

    public function gerarRelatorioDiario() {
        try {
            $hoje = date('Y-m-d');
            
            // Verificar se já existe relatório para hoje
            $stmt = $this->db->prepare("SELECT * FROM relatorios_diarios WHERE data = ?");
            $stmt->execute([$hoje]);
            $relatorioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            // Obter dados para o relatório
            $estatisticas = $this->obterEstatisticas();
            
            // Cancelamentos hoje
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE DATE(data_atualizacao) = ? AND status = 'Cancelado'");
            $stmt->execute([$hoje]);
            $cancelamentos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Expirações hoje (reservas que passaram da data sem serem concluídas)
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservas WHERE data < CURDATE() AND status = 'Reservado'");
            $stmt->execute();
            $expiracoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Atualizar reservas expiradas
            $stmt = $this->db->prepare("UPDATE reservas SET status = 'Expirado' WHERE data < CURDATE() AND status = 'Reservado'");
            $stmt->execute();

            $dadosRelatorio = [
                'data' => $hoje,
                'num_reservas' => $estatisticas['reservasHoje'],
                'num_mesas_ocupadas' => $estatisticas['mesasOcupadas'],
                'num_expiracoes' => $expiracoes,
                'num_cancelamentos' => $cancelamentos,
                'percent_ocupacao' => $estatisticas['taxaOcupacao']
            ];

            if ($relatorioExistente) {
                // Atualizar relatório existente
                $stmt = $this->db->prepare("UPDATE relatorios_diarios SET num_reservas = ?, num_mesas_ocupadas = ?, num_expiracoes = ?, num_cancelamentos = ?, percent_ocupacao = ? WHERE data = ?");
                $stmt->execute([
                    $dadosRelatorio['num_reservas'],
                    $dadosRelatorio['num_mesas_ocupadas'],
                    $dadosRelatorio['num_expiracoes'],
                    $dadosRelatorio['num_cancelamentos'],
                    $dadosRelatorio['percent_ocupacao'],
                    $hoje
                ]);
            } else {
                // Criar novo relatório
                $stmt = $this->db->prepare("INSERT INTO relatorios_diarios (data, num_reservas, num_mesas_ocupadas, num_expiracoes, num_cancelamentos, percent_ocupacao) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $dadosRelatorio['data'],
                    $dadosRelatorio['num_reservas'],
                    $dadosRelatorio['num_mesas_ocupadas'],
                    $dadosRelatorio['num_expiracoes'],
                    $dadosRelatorio['num_cancelamentos'],
                    $dadosRelatorio['percent_ocupacao']
                ]);
            }

            return [
                'sucesso' => true,
                'mensagem' => 'Relatório diário gerado com sucesso!',
                'dados' => $dadosRelatorio
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ];
        }
    }

    public function obterClientes($busca = '') {
        try {
            if (empty($busca)) {
                $stmt = $this->db->query("
                    SELECT c.*, 
                           COUNT(r.id) as total_reservas,
                           MAX(r.data) as ultima_reserva
                    FROM clientes c
                    LEFT JOIN reservas r ON c.id = r.cliente_id
                    GROUP BY c.id
                    ORDER BY c.nome
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->db->prepare("
                    SELECT c.*, 
                           COUNT(r.id) as total_reservas,
                           MAX(r.data) as ultima_reserva
                    FROM clientes c
                    LEFT JOIN reservas r ON c.id = r.cliente_id
                    WHERE c.nome LIKE ? OR c.email LIKE ?
                    GROUP BY c.id
                    ORDER BY c.nome
                ");
                $busca = "%$busca%";
                $stmt->execute([$busca, $busca]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            return [];
        }
    }

    public function obterUsuariosSistema() {
        try {
            $stmt = $this->db->query("SELECT id, nome, email, tipo, data_criacao FROM usuarios_sistema ORDER BY nome");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function adicionarUsuarioSistema($dados) {
        try {
            // Verificar se email já existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios_sistema WHERE email = ?");
            $stmt->execute([$dados['email']]);
            if ($stmt->fetch()) {
                return [
                    'sucesso' => false,
                    'erro' => 'Este email já está sendo usado'
                ];
            }

            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("INSERT INTO usuarios_sistema (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $dados['nome'],
                $dados['email'],
                $senhaHash,
                $dados['tipo']
            ]);

            return [
                'sucesso' => true,
                'mensagem' => 'Usuário adicionado com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao adicionar usuário: ' . $e->getMessage()
            ];
        }
    }

    public function editarUsuarioSistema($dados) {
        try {
            // Verificar se email já existe (para outro usuário)
            $stmt = $this->db->prepare("SELECT id FROM usuarios_sistema WHERE email = ? AND id != ?");
            $stmt->execute([$dados['email'], $dados['id']]);
            if ($stmt->fetch()) {
                return [
                    'sucesso' => false,
                    'erro' => 'Este email já está sendo usado por outro usuário'
                ];
            }

            if (!empty($dados['senha'])) {
                // Atualizar com nova senha
                $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("UPDATE usuarios_sistema SET nome = ?, email = ?, senha = ?, tipo = ? WHERE id = ?");
                $stmt->execute([
                    $dados['nome'],
                    $dados['email'],
                    $senhaHash,
                    $dados['tipo'],
                    $dados['id']
                ]);
            } else {
                // Atualizar sem alterar senha
                $stmt = $this->db->prepare("UPDATE usuarios_sistema SET nome = ?, email = ?, tipo = ? WHERE id = ?");
                $stmt->execute([
                    $dados['nome'],
                    $dados['email'],
                    $dados['tipo'],
                    $dados['id']
                ]);
            }

            return [
                'sucesso' => true,
                'mensagem' => 'Usuário atualizado com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao atualizar usuário: ' . $e->getMessage()
            ];
        }
    }

    public function removerUsuarioSistema($usuarioId) {
        try {
            // Verificar se é o último administrador
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM usuarios_sistema WHERE tipo = 'Administrador'");
            $stmt->execute();
            $totalAdmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $this->db->prepare("SELECT tipo FROM usuarios_sistema WHERE id = ?");
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario['tipo'] === 'Administrador' && $totalAdmins <= 1) {
                return [
                    'sucesso' => false,
                    'erro' => 'Não é possível remover o último administrador do sistema'
                ];
            }

            $stmt = $this->db->prepare("DELETE FROM usuarios_sistema WHERE id = ?");
            $stmt->execute([$usuarioId]);

            return [
                'sucesso' => true,
                'mensagem' => 'Usuário removido com sucesso!'
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao remover usuário: ' . $e->getMessage()
            ];
        }
    }

// Adicionar ao final da classe AdminController, antes do fechamento

public function obterMesasDisponiveis($data, $hora, $numPessoas) {
    try {
        $stmt = $this->db->prepare("
            SELECT m.* FROM mesas m 
            WHERE m.capacidade >= ? 
            AND m.id NOT IN (
                SELECT r.mesa_id FROM reservas r 
                WHERE r.data = ? AND r.hora = ? AND r.status = 'Reservado'
            )
            ORDER BY m.capacidade ASC
        ");
        $stmt->execute([$numPessoas, $data, $hora]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

public function criarReservaComoAdmin($clienteId, $mesaId, $data, $hora, $numPessoas) {
    try {
        // Verificar se a mesa está disponível
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as conflitos FROM reservas 
            WHERE mesa_id = ? AND data = ? AND hora = ? AND status = 'Reservado'
        ");
        $stmt->execute([$mesaId, $data, $hora]);
        $conflitos = $stmt->fetch(PDO::FETCH_ASSOC)['conflitos'];

        if ($conflitos > 0) {
            return [
                'sucesso' => false,
                'erro' => 'Mesa não disponível para este horário'
            ];
        }

        // Verificar capacidade da mesa
        $stmt = $this->db->prepare("SELECT capacidade FROM mesas WHERE id = ?");
        $stmt->execute([$mesaId]);
        $mesa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mesa || $mesa['capacidade'] < $numPessoas) {
            return [
                'sucesso' => false,
                'erro' => 'Mesa não adequada para o número de pessoas'
            ];
        }

        // Criar reserva
        $stmt = $this->db->prepare("
            INSERT INTO reservas (cliente_id, mesa_id, data, hora, num_pessoas, status) 
            VALUES (?, ?, ?, ?, ?, 'Reservado')
        ");
        $stmt->execute([$clienteId, $mesaId, $data, $hora, $numPessoas]);

        return [
            'sucesso' => true,
            'mensagem' => 'Reserva criada com sucesso!',
            'reserva_id' => $this->db->lastInsertId()
        ];
    } catch (Exception $e) {
        return [
            'sucesso' => false,
            'erro' => 'Erro ao criar reserva: ' . $e->getMessage()
        ];
    }
}

public function obterReservasAdmin($filtro = 'todas') {
    try {
        $where = '';
        $params = [];

        switch ($filtro) {
            case 'hoje':
                $where = 'WHERE r.data = CURDATE()';
                break;
            case 'futuras':
                $where = 'WHERE r.data >= CURDATE() AND r.status = "Reservado"';
                break;
            case 'passadas':
                $where = 'WHERE r.data < CURDATE()';
                break;
        }

        $stmt = $this->db->prepare("
            SELECT r.*, c.nome as cliente_nome, c.email as cliente_email, 
                   m.capacidade as mesa_capacidade
            FROM reservas r
            JOIN clientes c ON r.cliente_id = c.id
            JOIN mesas m ON r.mesa_id = m.id
            $where
            ORDER BY r.data DESC, r.hora DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

public function cancelarReservaAdmin($reservaId) {
    try {
        $stmt = $this->db->prepare("
            UPDATE reservas SET status = 'Cancelado', data_atualizacao = NOW() 
            WHERE id = ? AND status = 'Reservado'
        ");
        $stmt->execute([$reservaId]);

        if ($stmt->rowCount() > 0) {
            return [
                'sucesso' => true,
                'mensagem' => 'Reserva cancelada com sucesso!'
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => 'Reserva não encontrada ou já foi processada'
            ];
        }
    } catch (Exception $e) {
        return [
            'sucesso' => false,
            'erro' => 'Erro ao cancelar reserva: ' . $e->getMessage()
        ];
    }
}

}


?>
