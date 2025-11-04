<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once "../includes/conexao.php";
require_once "../log_helper.php";

mysqli_report(MYSQLI_REPORT_OFF);

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

    $id_fornecedor = trim($_GET["id"]);

    $nome_fornecedor = "";
    $sql_nome = "SELECT nome FROM fornecedores WHERE id_fornecedor = " . $id_fornecedor;
    $result_nome = mysqli_query($link, $sql_nome);
    if ($result_nome && mysqli_num_rows($result_nome) > 0) {
        $row_nome = mysqli_fetch_assoc($result_nome);
        $nome_fornecedor = $row_nome['nome'];
    }

    $sql = "DELETE FROM fornecedores WHERE id_fornecedor = ?";

    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);

        $param_id = $id_fornecedor;

        if(mysqli_stmt_execute($stmt)){
            $acao = "Excluiu Fornecedor";
            $detalhes = "Fornecedor excluído: " . $nome_fornecedor . " (ID: " . $id_fornecedor . ").";
            registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);

            header("location: index.php?status=success_delete");
            exit();
        } else{
            $error_code = mysqli_errno($link);
            if ($error_code == 1451) {
                header("location: index.php?status=fk_error");
                exit();
            } else {
                header("location: index.php?status=general_error");
                exit();
            }
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($link);

} else{
    header("location: index.php");
    exit();
}