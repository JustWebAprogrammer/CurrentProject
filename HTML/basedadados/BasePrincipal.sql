SHOW databases;
create database SiteDonaXinga;
USE SiteDonaXinga;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telemovel VARCHAR(20) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE mesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    capacidade INT NOT NULL,
    estado ENUM('Livre', 'Ocupada') NOT NULL DEFAULT 'Livre'
);





CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    mesa_id INT NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL,
    num_pessoas INT NOT NULL,
    status ENUM('Reservado', 'Concluído', 'Cancelado', 'Expirado') NOT NULL DEFAULT 'Reservado',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (mesa_id) REFERENCES mesas(id),
    CHECK (num_pessoas >= 0 AND num_pessoas <= 60)
);


CREATE TABLE usuarios_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('Rececionista', 'Administrador') NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE relatorios_diarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL UNIQUE,
    num_reservas INT NOT NULL DEFAULT 0,
    num_mesas_ocupadas INT NOT NULL DEFAULT 0,
    num_expiracoes INT NOT NULL DEFAULT 0,
    num_cancelamentos INT NOT NULL DEFAULT 0,
    percent_ocupacao DECIMAL(5,2) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alertas_falhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mensagem VARCHAR(255) NOT NULL,
    reserva_id INT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id)
);

ALTER TABLE reservas ADD COLUMN reserva_principal_id INT NULL;
ALTER TABLE reservas ADD FOREIGN KEY (reserva_principal_id) REFERENCES reservas(id);


Select * From reservas;