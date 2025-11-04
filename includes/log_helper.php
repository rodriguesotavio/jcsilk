<?php
date_default_timezone_set('America/Sao_Paulo');

function registrar_acao($link, $acao, $detalhes, $id_usuario){
    $sql = "INSERT INTO acoes (id_usuario, acao, data_acao, detalhes) VALUES (?, ?, ?, ?)";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "isss", $param_id_usuario, $param_acao, $param_data_movimento, $param_detalhes);

        $param_id_usuario = $id_usuario;
        $param_acao = $acao;
        $param_detalhes = $detalhes;
        $param_data_movimento = date('Y-m-d H:i:s');

        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("Erro ao registrar ação no log: " . mysqli_error($link));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    return false;
}
