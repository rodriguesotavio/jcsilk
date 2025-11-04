<?php
require_once '../includes/header.php';

$id_produto = $tipo_movimento = $quantidade = "";
$produto_err = $movimento_err = $quantidade_err = "";

$produtos = [];
$sql_produtos = "SELECT id_produto, nome_produto, quantidade_estoque FROM produtos ORDER BY nome_produto ASC";
$result_produtos = mysqli_query($link, $sql_produtos);

if ($result_produtos) {
    while ($row = mysqli_fetch_assoc($result_produtos)) {
        $produtos[] = $row;
    }
    mysqli_free_result($result_produtos);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $id_produto = trim($_POST["id_produto"]);
    $tipo_movimento = trim($_POST["tipo_movimento"]);
    $quantidade = trim($_POST["quantidade"]);

    if(empty($id_produto)){
        $produto_err = "Selecione um produto.";
    }

    if(empty($tipo_movimento)){
        $movimento_err = "Selecione o tipo de movimento.";
    } elseif ($tipo_movimento !== 'Entrada' && $tipo_movimento !== 'Saída') {
        $movimento_err = "Tipo de movimento inválido.";
    }

    if(!ctype_digit($quantidade) || $quantidade <= 0){
        $quantidade_err = "A quantidade deve ser um número inteiro positivo.";
    }

    if(empty($produto_err) && empty($movimento_err) && empty($quantidade_err)){
        $sql_estoque_atual = "SELECT nome_produto, quantidade_estoque, preco_unitario FROM produtos WHERE id_produto = ?";
        if($stmt_estoque = mysqli_prepare($link, $sql_estoque_atual)){
            mysqli_stmt_bind_param($stmt_estoque, "i", $param_id_produto);
            $param_id_produto = $id_produto;
            mysqli_stmt_execute($stmt_estoque);
            $result_estoque = mysqli_stmt_get_result($stmt_estoque);
            $row_estoque = mysqli_fetch_assoc($result_estoque);

            $estoque_atual = $row_estoque['quantidade_estoque'];
            mysqli_stmt_close($stmt_estoque);

            if($tipo_movimento == 'Saída' && $quantidade > $estoque_atual){
                $quantidade_err = "Estoque insuficiente. Estoque atual: {$estoque_atual}.";
            }
        }
    }

    if(empty($produto_err) && empty($movimento_err) && empty($quantidade_err)){

        $operacao_estoque = ($tipo_movimento == 'Entrada') ? ' + ?' : ' - ?';

        $sql_update_produto = "UPDATE produtos SET quantidade_estoque = quantidade_estoque" . $operacao_estoque . " WHERE id_produto = ?";

        if($stmt_update = mysqli_prepare($link, $sql_update_produto)){
            mysqli_stmt_bind_param($stmt_update, "ii", $param_quantidade, $param_id_produto_update);
            $param_quantidade = $quantidade;
            $param_id_produto_update = $id_produto;

            if(!mysqli_stmt_execute($stmt_update)){
                echo '<div class="alert alert-danger">Erro ao atualizar o estoque do produto.</div>';
            }
            mysqli_stmt_close($stmt_update);
        }

        $sql_insert_estoque = "INSERT INTO estoque (id_produto, tipo_movimento, quantidade, data_movimento, id_usuario) VALUES (?, ?, ?, ?, ?)";

        if($stmt_insert = mysqli_prepare($link, $sql_insert_estoque)){
            mysqli_stmt_bind_param($stmt_insert, "isisi", $param_id_produto, $param_tipo, $param_qtd, $param_data_movimento, $param_id_usuario);

            $param_id_produto = $id_produto;
            $param_tipo = $tipo_movimento;
            $param_qtd = $quantidade;
            $param_data_movimento = date('Y-m-d H:i:s');
            $param_id_usuario = $_SESSION['id_usuario'];

            if(mysqli_stmt_execute($stmt_insert)){
                $nome_produto = $row_estoque['nome_produto'];
                $acao = "Movimento de Estoque: " . $tipo_movimento;
                $detalhes = "Produto: " . $nome_produto . " (ID: " . $id_produto . "). Tipo: " . $tipo_movimento . ". Qtd: " . $quantidade . ". Vl. Unitário: R$" . number_format($row_estoque['preco_unitario'], 2, ',', '.');
                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);

                header("location: index.php?status=success_move");
                exit();
            }
            mysqli_stmt_close($stmt_insert);
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Nova Movimentação de Estoque</h2>
</div>

<p>Registre entradas (compras/devoluções) ou saídas (vendas/perdas) de produtos.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_produto" class="form-label">Produto (*)</label>
            <select name="id_produto" id="id_produto" class="form-select <?php echo (!empty($produto_err)) ? 'is-invalid' : ''; ?>" required>
                <option value="">Selecione o Produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?php echo $produto['id_produto']; ?>"
                        <?php echo ($produto['id_produto'] == $id_produto) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($produto['nome_produto']); ?> (Estoque: <?php echo $produto['quantidade_estoque']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?php echo $produto_err; ?></div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="tipo_movimento" class="form-label">Tipo de Movimento (*)</label>
            <select name="tipo_movimento" id="tipo_movimento" class="form-select <?php echo (!empty($movimento_err)) ? 'is-invalid' : ''; ?>" required>
                <option value="">Selecione</option>
                <option value="Entrada" <?php echo ($tipo_movimento == 'Entrada') ? 'selected' : ''; ?>>Entrada (Aumenta Estoque)</option>
                <option value="Saída" <?php echo ($tipo_movimento == 'Saída') ? 'selected' : ''; ?>>Saída (Diminui Estoque)</option>
            </select>
            <div class="invalid-feedback"><?php echo $movimento_err; ?></div>
        </div>
    </div>

    <div class="mb-3">
        <label for="quantidade" class="form-label">Quantidade (*)</label>
        <input type="number" name="quantidade" id="quantidade" class="form-control <?php echo (!empty($quantidade_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($quantidade); ?>" min="1" required>
        <div class="invalid-feedback"><?php echo $quantidade_err; ?></div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary me-2">
        <i class="fas fa-arrow-right-arrow-left me-1"></i> Registrar Movimentação
    </button>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-times me-1"></i> Cancelar
    </a>
</form>

<?php
require_once '../includes/footer.php';
?>
