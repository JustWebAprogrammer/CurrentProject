<?php
class Cliente {
    private $conn;
    private $table_name = "clientes";

    public $id;
    public $nome;
    public $email;
    public $telemovel;
    public $senha;
    public $data_criacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, telemovel=:telemovel, senha=:senha";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telemovel = htmlspecialchars(strip_tags($this->telemovel));
        $this->senha = password_hash($this->senha, PASSWORD_DEFAULT);

        // Bind dos valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telemovel", $this->telemovel);
        $stmt->bindParam(":senha", $this->senha);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    function atualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, email=:email, telemovel=:telemovel 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Limpar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telemovel = htmlspecialchars(strip_tags($this->telemovel));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind dos valores
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telemovel", $this->telemovel);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    function buscarPorEmail($email) {
        $query = "SELECT id, nome, email, telemovel, senha, data_criacao 
                  FROM " . $this->table_name . " 
                  WHERE email = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }

        return false;
    }

    function buscarPorTelefone($telemovel) {
        $query = "SELECT id, nome, email, telemovel 
                  FROM " . $this->table_name . " 
                  WHERE telemovel = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $telemovel);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }

        return false;
    }

    function verificarSenha($senha, $hash) {
        return password_verify($senha, $hash);
    }
}