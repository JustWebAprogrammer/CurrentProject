<?php
class ReservaValidator {
    
    public function validarData($data) {
        $dataReserva = new DateTime($data);
        $hoje = new DateTime();
        $maxData = new DateTime();
        $maxData->add(new DateInterval('P7D'));

        $hoje->setTime(0, 0, 0);
        $maxData->setTime(0, 0, 0);
        $dataReserva->setTime(0, 0, 0);

        return $dataReserva >= $hoje && $dataReserva <= $maxData;
    }

    public function validarHorario($hora) {
        $horario = DateTime::createFromFormat('H:i', $hora);
        if (!$horario) return false;

        $horaInt = (int)$horario->format('H');
        return $horaInt >= 9 && $horaInt <= 22;
    }

    public function validarNumPessoas($numPessoas) {
        $num = (int)$numPessoas;
        return $num >= 1 && $num <= 60;
    }
}
?>