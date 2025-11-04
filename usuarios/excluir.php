<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "index.php?status=permission_denied");
    exit();
}

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

    $id_usuario_alvo = trim($_GET["id"]);
    $id_usuario_sessao = $_SESSION['id_usuario'];

    if ($id_usuario_alvo == $id_usuario_sessao) {
        header("location: index.php?status=cannot_self_delete");
        exit();
    }

    $nome_alvo = "";
    $sql_nome = "SELECT nome FROM usuarios WHERE id_usuario = " . $id_usuario_alvo;
    $result_nome = mysqli_query($link, $sql_nome);
    if ($result_nome && mysqli_num_rows($result_nome) > 0) {
        $row_nome = mysqli_fetch_assoc($result_nome);
        $nome_alvo = $row_nome['nome'];
    }

    $sql = "UPDATE usuarios SET ativo = 0 WHERE id_usuario = ?";

    if($stmt = mysqli_prepare($link, $sql)){

        mysqli_stmt_bind_param($stmt, "i", $param_id);

        $param_id = $id_usuario_alvo;

        if(mysqli_stmt_execute($stmt)){
            $acao = "Inativou Usuário";
            $detalhes = "Usuário inativado: " . $nome_alvo . " (ID: " . $id_usuario_alvo . "). Status alterado para Inativo.";
            registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);

            header("location: index.php?status=success_delete");
            exit();
        } else{
            echo "Erro! Não foi possível inativar o usuário. Tente novamente mais tarde.";
        }

        mysqli_stmt_close($stmt);
    }

} else{
    header("location: index.php");
    exit();
}
?>