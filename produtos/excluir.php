<?php
require_once '../includes/header.php';

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

    $id_produto = trim($_GET["id"]);

    $sql = "DELETE FROM produtos WHERE id_produto = ?";

    if($stmt = mysqli_prepare($link, $sql)){

        mysqli_stmt_bind_param($stmt, "i", $param_id);

        $param_id = $id_produto;

        if(mysqli_stmt_execute($stmt)){
            header("location: index.php?status=success_delete");
            exit();
        } else{
            echo "Erro! Não foi possível excluir o produto. Tente novamente mais tarde.";
        }

        mysqli_stmt_close($stmt);
    }

} else {
    header("location: index.php");
    exit();
}
?>