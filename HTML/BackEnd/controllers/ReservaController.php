<?php
require_once '../models/Reserva.php';
require_once '../utils/validation.php';

class ReservaController {
    private $db;
    private $reserva;

    public function __construct($database) {
        $this->db = $database;
        $this->reserva = new Reserva($this->db);
    }

    public function criarReserva($dados) {
        // Validar dados
        $validacao = $this->validarDadosReserva($dados);
        if (!$validacao['valido']) {
            return [
                'sucesso' => false,
                'erro' => $validacao['erro']
            ];
        }
    
        // Verificar disponibilidade de mesas
        $mesasDisponiveis = $this->reserva->buscarMesasDisponiveis(
            $dados['data'],
            $dados['hora'],
            $dados['num_pessoas']
        );
    
        $mesasNecessarias = ceil($dados['num_pessoas'] / 4);
        
        if (count($mesasDisponiveis) < $mesasNecessarias) {
            return [
                'sucesso' => false,
                'erro' => 'Não há mesas suficientes disponíveis para este horário.'
            ];
        }
    
        // Criar APENAS UMA reserva principal
        $this->reserva->cliente_id = $dados['cliente_id'];
        $this->reserva->mesa_id = $mesasDisponiveis[0]['id']; // Mesa principal
        $this->reserva->data = $dados['data'];
        $this->reserva->hora = $dados['hora'];
        $this->reserva->num_pessoas = $dados['num_pessoas']; // Número total de pessoas
        $this->reserva->status = 'Reservado';
    
        if ($this->reserva->criar()) {
            $reserva_principal_id = $this->db->lastInsertId();
            
            // Se precisar de mais mesas, criar reservas auxiliares (apenas para bloquear as mesas)
            $reservas_auxiliares = [];
            for ($i = 1; $i < $mesasNecessarias; $i++) {
                if ($this->criarReservaAuxiliar($dados, $mesasDisponiveis[$i]['id'], $reserva_principal_id)) {
                    $reservas_auxiliares[] = $this->db->lastInsertId();
                } else {
                    // Se falhar, cancelar tudo
                    $this->cancelarReserva($reserva_principal_id);
                    $this->cancelarReservas($reservas_auxiliares);
                    return [
                        'sucesso' => false,
                        'erro' => 'Erro ao reservar todas as mesas necessárias.'
                    ];
                }
            }
    
            return [
                'sucesso' => true,
                'mensagem' => 'Reserva criada com sucesso!',
                'reserva_id' => $reserva_principal_id,
                'mesas_reservadas' => $mesasNecessarias
            ];
        } else {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao criar reserva.'
            ];
        }
    }
    
    // Novo método para criar reservas auxiliares (só para bloquear mesas)
    private function criarReservaAuxiliar($dados, $mesa_id, $reserva_principal_id) {
        
        if (!isset($dados['cliente_id']) || empty($dados['cliente_id'])) {
            error_log(message: "ERRO: cliente_id não encontrado nos dados para reserva auxiliar");
            return false;
        }
        
        $query = "INSERT INTO reservas (cliente_id, mesa_id, data, hora, num_pessoas, status, reserva_principal_id) 
                  VALUES (:cliente_id, :mesa_id, :data, :hora, 0, 'Auxiliar', :reserva_principal_id)";
        
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(":cliente_id", $dados['cliente_id']);
        $stmt->bindParam(":mesa_id", $mesa_id);
        $stmt->bindParam(":data", $dados['data']);
        $stmt->bindParam(":hora", $dados['hora']);
        $stmt->bindParam(":reserva_principal_id", $reserva_principal_id);
        
        return $stmt->execute();
    }


    public function editarReserva($dados) {
   // Verificar se a reserva existe e pode ser editada
   $reservaExistente = $this->buscarReservaPorId($dados['reserva_id']);
   if (!$reservaExistente) {
       return [
           'sucesso' => false,
           'erro' => 'Reserva não encontrada.'
       ];
   }

    // ADICIONAR: Incluir cliente_id nos dados para operações subsequentes
    $dados['cliente_id'] = $reservaExistente['cliente_id'];


   // Verificar se a reserva não foi cancelada
   if ($reservaExistente['status'] === 'Cancelado') {
       return [
           'sucesso' => false,
           'erro' => 'Não é possível editar uma reserva cancelada.'
       ];
   }

   // Verificar se ainda é possível editar (2 horas antes)
   $dataHoraReserva = new DateTime($reservaExistente['data'] . ' ' . $reservaExistente['hora']);
   $agora = new DateTime();
   $diferenca = $agora->diff($dataHoraReserva);
   
   $totalMinutos = ($diferenca->days * 24 * 60) + ($diferenca->h * 60) + $diferenca->i;
   if ($agora > $dataHoraReserva) {
       $totalMinutos = -$totalMinutos;
   }
   $horasRestantes = $totalMinutos / 60;

   if ($horasRestantes < 2) {
       return [
           'sucesso' => false,
           'erro' => 'Não é possível editar a reserva com menos de 2 horas de antecedência.'
       ];
   }

   // Validar novos dados
   $validacao = $this->validarDadosReserva($dados);
   if (!$validacao['valido']) {
       return [
           'sucesso' => false,
           'erro' => $validacao['erro']
       ];
   }

   // Verificar se houve mudanças significativas
   $mudouDataHora = ($dados['data'] != $reservaExistente['data'] || $dados['hora'] != $reservaExistente['hora']);
   $mudouPessoas = ($dados['num_pessoas'] != $reservaExistente['num_pessoas']);
   
   // Verificar disponibilidade de mesas apenas se necessário
   if ($mudouDataHora || $mudouPessoas) {
       $mesasDisponiveis = $this->reserva->buscarMesasDisponiveisParaEdicao(
           $dados['data'],
           $dados['hora'],
           $dados['num_pessoas'],
           $dados['reserva_id']
       );
       
       $mesasNecessarias = ceil($dados['num_pessoas'] / 4);
       $mesasAtuais = $this->contarMesasReserva($dados['reserva_id']);
       
       if ($mesasNecessarias > $mesasAtuais) {
           $mesasAdicionais = $mesasNecessarias - $mesasAtuais;
           if (count($mesasDisponiveis) < $mesasAdicionais) {
               return [
                   'sucesso' => false,
                   'erro' => 'Não há mesas suficientes disponíveis para este horário.'
               ];
           }
       }
       
       if ($mesasNecessarias < $mesasAtuais) {
           $this->cancelarMesasExtras($dados['reserva_id'], $mesasNecessarias);
       }
       
       if ($mesasNecessarias > $mesasAtuais) {
           $this->criarMesasAdicionais($dados, $mesasDisponiveis, $mesasAtuais, $mesasNecessarias);
       }
   }

   // Atualizar reserva principal
   $this->reserva->id = $dados['reserva_id'];
   $this->reserva->data = $dados['data'];
   $this->reserva->hora = $dados['hora'];
   $this->reserva->num_pessoas = $dados['num_pessoas'];

   if ($this->reserva->atualizar()) {
       return [
           'sucesso' => true,
           'mensagem' => 'Reserva atualizada com sucesso!'
       ];
   } else {
       return [
           'sucesso' => false,
           'erro' => 'Erro ao atualizar reserva no banco de dados.'
       ];
   }
}

// NOVO MÉTODO - Buscar mesas disponíveis excluindo a reserva atual
private function buscarMesasDisponiveisParaEdicao($data, $hora, $num_pessoas, $reserva_atual_id) {
    $mesasNecessarias = ceil($num_pessoas / 4);
    
    $query = "SELECT m.id, m.capacidade 
              FROM mesas m 
              WHERE m.estado = 'Livre' 
              AND m.id NOT IN (
                  SELECT r.mesa_id 
                  FROM reservas r 
                  WHERE r.data = :data 
                  AND r.hora = :hora 
                  AND r.status IN ('Reservado', 'Auxiliar')
                  AND r.id != :reserva_atual_id 
                  AND (r.reserva_principal_id IS NULL OR r.reserva_principal_id != :reserva_atual_id)
              )
              ORDER BY m.capacidade ASC 
              LIMIT :mesas_necessarias";

    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":data", $data);
    $stmt->bindParam(":hora", $hora);
    $stmt->bindParam(":reserva_atual_id", $reserva_atual_id);
    $stmt->bindParam(":mesas_necessarias", $mesasNecessarias, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Métodos auxiliares para gerenciar mesas na edição
private function contarMesasReserva($reserva_id) {
    $query = "SELECT COUNT(*) as total FROM reservas 
              WHERE id = :reserva_id OR reserva_principal_id = :reserva_id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":reserva_id", $reserva_id);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado['total'];
}

private function cancelarMesasExtras($reserva_id, $mesas_necessarias) {
    // Manter apenas o número necessário de mesas
    $query = "UPDATE reservas SET status = 'Cancelado' 
              WHERE reserva_principal_id = :reserva_id 
              AND id NOT IN (
                  SELECT * FROM (
                      SELECT id FROM reservas 
                      WHERE reserva_principal_id = :reserva_id2 
                      ORDER BY id ASC 
                      LIMIT :limite
                  ) as subquery
              )";
    
    $stmt = $this->db->prepare($query);
    $limite = $mesas_necessarias - 1; // -1 porque a principal não conta
    $stmt->bindParam(":reserva_id", $reserva_id);
    $stmt->bindParam(":reserva_id2", $reserva_id);
    $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
    $stmt->execute();
}

private function criarMesasAdicionais($dados, $mesasDisponiveis, $mesasAtuais, $mesasNecessarias) {
    $mesasParaCriar = $mesasNecessarias - $mesasAtuais;
    
    // Garantir que temos o cliente_id
    if (!isset($dados['cliente_id'])) {
        $reservaExistente = $this->buscarReservaPorId($dados['reserva_id']);
        $dados['cliente_id'] = $reservaExistente['cliente_id'];
    }
    
    for ($i = 0; $i < $mesasParaCriar && $i < count($mesasDisponiveis); $i++) {
        $this->criarReservaAuxiliar($dados, $mesasDisponiveis[$i]['id'], $dados['reserva_id']);
    }
}

    private function buscarReservaPorId($id) {
        $query = "SELECT * FROM reservas WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // MÉTODO ATUALIZADO PARA INCLUIR FILTRO
   public function buscarReservasPorCliente($cliente_id, $filtro = 'proximas') {
    $hoje = date('Y-m-d');
    $agora = date('H:i:s');
    
    switch($filtro) {
        case 'proximas':
            $query = "SELECT r.*, m.capacidade,
                             (SELECT COUNT(*) FROM reservas r2 
                              WHERE (r2.reserva_principal_id = r.id OR r2.id = r.id) 
                              AND r2.status IN ('Reservado', 'Auxiliar')) as total_mesas
                      FROM reservas r 
                      JOIN mesas m ON r.mesa_id = m.id 
                      WHERE r.cliente_id = :cliente_id 
                      AND r.status = 'Reservado'
                      AND r.num_pessoas > 0
                      AND (r.data > :hoje OR (r.data = :hoje AND r.hora >= :agora))
                      ORDER BY r.data ASC, r.hora ASC";
            break;
            
        case 'passadas':
            $query = "SELECT r.*, m.capacidade,
                             (SELECT COUNT(*) FROM reservas r2 
                              WHERE (r2.reserva_principal_id = r.id OR r2.id = r.id) 
                              AND r2.status IN ('Reservado', 'Auxiliar', 'Concluído')) as total_mesas
                      FROM reservas r 
                      JOIN mesas m ON r.mesa_id = m.id 
                      WHERE r.cliente_id = :cliente_id 
                      AND r.num_pessoas > 0
                      AND (r.status = 'Concluído' OR 
                           (r.status = 'Reservado' AND (r.data < :hoje OR (r.data = :hoje AND r.hora < :agora))))
                      ORDER BY r.data DESC, r.hora DESC";
            break;
            
        case 'canceladas':
            $query = "SELECT r.*, m.capacidade,
                             (SELECT COUNT(*) FROM reservas r2 
                              WHERE (r2.reserva_principal_id = r.id OR r2.id = r.id) 
                              AND r2.status = 'Cancelado') as total_mesas
                      FROM reservas r 
                      JOIN mesas m ON r.mesa_id = m.id 
                      WHERE r.cliente_id = :cliente_id 
                      AND r.status = 'Cancelado'
                      AND r.num_pessoas > 0
                      ORDER BY r.data DESC, r.hora DESC";
            break;
            
        default:
            $query = "SELECT r.*, m.capacidade,
                             (SELECT COUNT(*) FROM reservas r2 
                              WHERE (r2.reserva_principal_id = r.id OR r2.id = r.id)) as total_mesas
                      FROM reservas r 
                      JOIN mesas m ON r.mesa_id = m.id 
                      WHERE r.cliente_id = :cliente_id 
                      AND r.num_pessoas > 0
                      ORDER BY r.data DESC, r.hora DESC";
    }
    
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":cliente_id", $cliente_id);
    
    if ($filtro === 'proximas' || $filtro === 'passadas') {
        $stmt->bindParam(":hoje", $hoje);
        $stmt->bindParam(":agora", $agora);
    }
    
    $stmt->execute();
    
    return [
        'sucesso' => true,
        'reservas' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

public function cancelarReserva($reserva_id) {
    // Cancelar a reserva principal
    $query = "UPDATE reservas SET status = 'Cancelado' WHERE id = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(":id", $reserva_id);
    
    if ($stmt->execute()) {
        // Cancelar também as reservas auxiliares
        $query_aux = "UPDATE reservas SET status = 'Cancelado' WHERE reserva_principal_id = :reserva_id";
        $stmt_aux = $this->db->prepare($query_aux);
        $stmt_aux->bindParam(":reserva_id", $reserva_id);
        $stmt_aux->execute();
        
        return [
            'sucesso' => true,
            'mensagem' => 'Reserva cancelada com sucesso!'
        ];
    } else {
        return [
            'sucesso' => false,
            'erro' => 'Erro ao cancelar reserva.'
        ];
    }
}

private function validarDadosReserva($dados) {
    $validator = new ReservaValidator();
    
    // Validar formato da data
    if (empty($dados['data']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data'])) {
        return ['valido' => false, 'erro' => 'Formato de data inválido. Use YYYY-MM-DD.'];
    }

    if (!$validator->validarData($dados['data'])) {
        return ['valido' => false, 'erro' => 'Data inválida ou fora do intervalo permitido (hoje até +7 dias).'];
    }

    // Validar formato do horário
    if (empty($dados['hora'])) {
        return ['valido' => false, 'erro' => 'Horário não fornecido. Selecione um horário válido.'];
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $dados['hora'])) {
        return ['valido' => false, 'erro' => 'Formato de horário inválido. Use HH:MM (ex.: 14:30).'];
    }

    if (!$validator->validarHorario($dados['hora'])) {
        return ['valido' => false, 'erro' => 'Horário deve ser entre 09:00 e 22:00.'];
    }

    if (!$validator->validarNumPessoas($dados['num_pessoas'])) {
        return ['valido' => false, 'erro' => 'Número de pessoas deve ser entre 1 e 60.'];
    }

    return ['valido' => true];
}

    private function cancelarReservas($ids) {
        foreach ($ids as $id) {
            $query = "UPDATE reservas SET status = 'Cancelado' WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
        }
    }
}
?>