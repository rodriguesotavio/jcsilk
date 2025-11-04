<?php
session_start();

require_once "./includes/conexao.php";
require_once "./includes/log_helper.php";

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    $id_usuario_log = $_SESSION['id_usuario'];
    $nome_usuario_log = $_SESSION['nome'];

    $acao = "Logout";
    $detalhes = "Usuário '{$nome_usuario_log}' (ID: {$id_usuario_log}) saiu do sistema.";
    registrar_acao($link, $acao, $detalhes, $id_usuario_log);
}

$_SESSION = array();
session_destroy();
header("location: login.php");
exit;