<?php
require_once '../models/Cliente.php';

class AuthController {
    private $db;
    private $cliente;

    public function __construct($database) {
        $this->db = $database;
        $this->cliente = new Cliente($this->db);
    }

    public function login($email, $senha) {
        // Buscar cliente por email
        $clienteData = $this->cliente->buscarPorEmail($email);
        
        if (!$clienteData) {
            return [
                'sucesso' => false,
                'erro' => 'Email ou senha incorretos'
            ];
        }

        // Verificar senha
        if (!password_verify($senha, $clienteData['senha'])) {
            return [
                'sucesso' => false,
                'erro' => 'Email ou senha incorretos'
            ];
        }

        // Login bem-sucedido
        return [
            'sucesso' => true,
            'mensagem' => 'Login realizado com sucesso',
            'cliente' => [
                'id' => $clienteData['id'],
                'nome' => $clienteData['nome'],
                'email' => $clienteData['email'],
                'telemovel' => $clienteData['telemovel']
            ]
        ];
    }
}
?>