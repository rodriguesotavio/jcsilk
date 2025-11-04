<?php
require_once '../includes/header.php';

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

    $id_movimentacao = trim($_GET["id"]);

    $sql_original = "SELECT id_produto, tipo_movimento, quantidade, data_movimento, estornado_de_id FROM estoque WHERE id_movimentacao = ?";

    if($stmt_original = mysqli_prepare($link, $sql_original)){
        mysqli_stmt_bind_param($stmt_original, "i", $param_id_mov);
        $param_id_mov = $id_movimentacao;

        if(mysqli_stmt_execute($stmt_original)){
            $result_original = mysqli_stmt_get_result($stmt_original);

            if(mysqli_num_rows($result_original) == 1){
                $original = mysqli_fetch_assoc($result_original);
                if (!is_null($original['estornado_de_id'])) {
                    header("location: index.php?status=already_reverted");
                    exit();
                }

                $sql_is_an_estorno = "SELECT id_movimentacao FROM estoque WHERE estornado_de_id = ?";
                if ($stmt_is_estorno = mysqli_prepare($link, $sql_is_an_estorno)) {
                    mysqli_stmt_bind_param($stmt_is_estorno, "i", $param_mov_id);
                    $param_mov_id = $id_movimentacao;
                    mysqli_stmt_execute($stmt_is_estorno);
                    mysqli_stmt_store_result($stmt_is_estorno);

                    if (mysqli_stmt_num_rows($stmt_is_estorno) > 0) {
                        header("location: index.php?status=cannot_revert_correction");
                        exit();
                    }
                    mysqli_stmt_close($stmt_is_estorno);
                }

                $id_produto = $original['id_produto'];
                $quantidade = $original['quantidade'];
                $data_movimento = strtotime($original['data_movimento']);

                $limite_segundos = LIMITE_DIAS_ESTORNO * 24 * 60 * 60;
                if ((time() - $data_movimento) > $limite_segundos) {
                    header("location: index.php?status=date_limit_exceeded");
                    exit();
                }

                $tipo_movimento_reverso = ($original['tipo_movimento'] == 'Entrada') ? 'Saída' : 'Entrada';

                if ($tipo_movimento_reverso == 'Saída') {
                    $sql_estoque_atual = "SELECT quantidade_estoque FROM produtos WHERE id_produto = ?";
                    if($stmt_estoque = mysqli_prepare($link, $sql_estoque_atual)){
                        mysqli_stmt_bind_param($stmt_estoque, "i", $param_id_produto_estoque);
                        $param_id_produto_estoque = $id_produto;
                        mysqli_stmt_execute($stmt_estoque);
                        $result_estoque = mysqli_stmt_get_result($stmt_estoque);
                        $row_estoque = mysqli_fetch_assoc($result_estoque);

                        $estoque_atual = $row_estoque['quantidade_estoque'];
                        mysqli_stmt_close($stmt_estoque);

                        if($quantidade > $estoque_atual){
                            header("location: index.php?status=revert_insufficient_stock");
                            exit();
                        }
                    }
                }

                $operacao_estoque = ($tipo_movimento_reverso == 'Entrada') ? ' + ?' : ' - ?';

                $sql_update_produto = "UPDATE produtos SET quantidade_estoque = quantidade_estoque" . $operacao_estoque . " WHERE id_produto = ?";

                if($stmt_update = mysqli_prepare($link, $sql_update_produto)){
                    mysqli_stmt_bind_param($stmt_update, "ii", $param_quantidade, $param_id_produto_update);
                    $param_quantidade = $quantidade;
                    $param_id_produto_update = $id_produto;

                    if(!mysqli_stmt_execute($stmt_update)){
                        echo '<div class="alert alert-danger">Erro fatal ao reverter o estoque do produto.</div>';
                        exit();
                    }
                    mysqli_stmt_close($stmt_update);
                }

                $sql_insert_estoque = "INSERT INTO estoque (id_produto, tipo_movimento, quantidade, data_movimento, id_usuario) VALUES (?, ?, ?, ?, ?)";

                if($stmt_insert = mysqli_prepare($link, $sql_insert_estoque)){
                    mysqli_stmt_bind_param($stmt_insert, "isisi", $param_id_produto_insert, $param_tipo, $param_qtd, $param_data_movimento, $param_id_usuario);

                    $param_id_produto_insert = $id_produto;
                    $param_tipo = $tipo_movimento_reverso;
                    $param_qtd = $quantidade;
                    $param_data_movimento = date('Y-m-d H:i:s');
                    $param_id_usuario = $_SESSION['id_usuario'];

                    if(mysqli_stmt_execute($stmt_insert)){
                        $id_estorno_inserido = mysqli_insert_id($link);
                        $sql_mark = "UPDATE estoque SET estornado_de_id = ? WHERE id_movimentacao = ?";
                        if($stmt_mark = mysqli_prepare($link, $sql_mark)){
                            mysqli_stmt_bind_param($stmt_mark, "ii", $param_id_estorno, $param_id_original);
                            $param_id_estorno = $id_estorno_inserido;
                            $param_id_original = $id_movimentacao;
                            mysqli_stmt_execute($stmt_mark);
                            mysqli_stmt_close($stmt_mark);
                        }

                        // Início auditoria
                        $sql_nome = "SELECT nome_produto FROM produtos WHERE id_produto = " . $id_produto;
                        $result_nome = mysqli_query($link, $sql_nome);
                        $nome_produto = "ID: " . $id_produto;
                        if ($result_nome && mysqli_num_rows($result_nome) > 0) {
                            $row_nome = mysqli_fetch_assoc($result_nome);
                            $nome_produto = $row_nome['nome_produto'];
                        }

                        $acao = "Estornou Movimentação";
                        $detalhes = "Estorno de " . $original['tipo_movimento'] . " (ID: " . $id_movimentacao . "). Produto: " . $nome_produto . " | Qtd: " . $quantidade . ".";
                        registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);
                        // Fim auditoria

                        header("location: index.php?status=success_revert");
                        exit();
                    } else{
                        echo '<div class="alert alert-warning">Estoque revertido, mas houve erro ao registrar o estorno no histórico.</div>';
                    }
                    mysqli_stmt_close($stmt_insert);
                }

            } else{
                header("location: index.php?status=move_not_found");
                exit();
            }
            mysqli_stmt_close($stmt_original);
        }
    }

} else{
    header("location: index.php");
    exit();
}