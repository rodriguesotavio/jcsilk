<?php
require_once '../includes/header.php';

$id_produto = $nome_produto = $descricao = $categoria = $preco_unitario = $fornecedor_id = "";
$quantidade_estoque = 0;
$nome_err = $preco_err = $fornecedor_err = "";

$fornecedores = [];
$sql_fornecedores = "SELECT id_fornecedor, nome FROM fornecedores ORDER BY nome ASC";
$result_fornecedores = mysqli_query($link, $sql_fornecedores);

if ($result_fornecedores) {
    while ($row = mysqli_fetch_assoc($result_fornecedores)) {
        $fornecedores[] = $row;
    }
    mysqli_free_result($result_fornecedores);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $id_produto = $_POST["id_produto"];

    $valor_digitado = trim($_POST["preco_unitario"]);
    $sanitized_value = str_replace('.', '', $valor_digitado);
    $sanitized_value = str_replace(',', '.', $sanitized_value);

    if(empty(trim($_POST["nome_produto"]))){
        $nome_err = "Por favor, insira o nome do produto.";
    } else{
        $nome_produto = trim($_POST["nome_produto"]);
    }

    if(empty($valor_digitado) || !is_numeric($sanitized_value)){
        $preco_err = "O preço deve ser um valor numérico válido.";
        $preco_unitario = $valor_digitado;
    } else{
        $preco_unitario = (float) $sanitized_value;
    }

    if(empty(trim($_POST["fornecedor_id"]))){
        $fornecedor_err = "Por favor, selecione um fornecedor.";
    } else{
        $fornecedor_id = trim($_POST["fornecedor_id"]);
    }

    $descricao = trim($_POST["descricao"]);
    $categoria = trim($_POST["categoria"]);

    $sql_original = "SELECT nome_produto, descricao, categoria, preco_unitario, fornecedor_id FROM produtos WHERE id_produto = ?";
    if ($stmt_orig = mysqli_prepare($link, $sql_original)) {
        mysqli_stmt_bind_param($stmt_orig, "i", $param_id_produto);
        $param_id_produto = $id_produto;
        mysqli_stmt_execute($stmt_orig);
        $result_orig = mysqli_stmt_get_result($stmt_orig);
        $row_orig = mysqli_fetch_assoc($result_orig);

        $original_nome = $row_orig["nome_produto"];
        $original_descricao = $row_orig["descricao"];
        $original_categoria = $row_orig["categoria"];
        $original_preco = $row_orig["preco_unitario"];
        $original_fornecedor_id = $row_orig["fornecedor_id"];
        mysqli_stmt_close($stmt_orig);
    }

    if(empty($nome_err) && empty($preco_err) && empty($fornecedor_err)){

        $sql = "UPDATE produtos SET nome_produto = ?, descricao = ?, categoria = ?, preco_unitario = ?, fornecedor_id = ? WHERE id_produto = ?";

        if($stmt = mysqli_prepare($link, $sql)){

            mysqli_stmt_bind_param($stmt, "sssdii", $param_nome, $param_descricao, $param_categoria, $param_preco, $param_fornecedor_id, $param_id);

            $param_nome = $nome_produto;
            $param_descricao = $descricao;
            $param_categoria = $categoria;
            $param_preco = $preco_unitario;
            $param_fornecedor_id = $fornecedor_id;
            $param_id = $id_produto;

            if(mysqli_stmt_execute($stmt)){
                // Inicio auditoria
                $modificacoes = [];

                if ($nome_produto != $original_nome) {
                    $modificacoes[] = "Nome: '{$original_nome}' -> '{$nome_produto}'";
                }
                if ($descricao != $original_descricao) {
                    $modificacoes[] = "Descrição: '{$original_descricao}' -> '{$descricao}'";
                }
                if ($categoria != $original_categoria) {
                    $modificacoes[] = "Categoria: '{$original_categoria}' -> '{$categoria}'";
                }
                if (number_format($preco_unitario, 2) != number_format($original_preco, 2)) {
                    $modificacoes[] = "Preço: R$" . number_format($original_preco, 2, ',', '.') . " -> R$" . number_format($preco_unitario, 2, ',', '.');
                }
                if ($fornecedor_id != $original_fornecedor_id) {
                    $modificacoes[] = "Fornecedor ID: {$original_fornecedor_id} -> {$fornecedor_id}";
                }

                $acao = "Editou Produto";
                if (count($modificacoes) > 0) {
                    $detalhes = "Produto ID {$id_produto}. Alterações: " . implode(" | ", $modificacoes);
                } else {
                    $detalhes = "Produto ID {$id_produto}. Nenhuma alteração significativa detectada.";
                }
                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);
                // Fim auditoria

                header("location: index.php?status=success_edit");
                exit();
            } else{
                echo '<div class="alert alert-danger">Erro ao tentar atualizar o produto. Tente novamente mais tarde.</div>';
            }

            mysqli_stmt_close($stmt);
        }
    }

} else {

    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

        $id_produto = trim($_GET["id"]);

        $sql = "SELECT nome_produto, descricao, categoria, preco_unitario, quantidade_estoque, fornecedor_id FROM produtos WHERE id_produto = ?";

        if($stmt = mysqli_prepare($link, $sql)){

            mysqli_stmt_bind_param($stmt, "i", $param_id);

            $param_id = $id_produto;

            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                    $nome_produto = $row["nome_produto"];
                    $descricao = $row["descricao"];
                    $categoria = $row["categoria"];
                    $preco_unitario = $row["preco_unitario"];
                    $quantidade_estoque = $row["quantidade_estoque"];
                    $fornecedor_id = $row["fornecedor_id"];
                } else{
                    header("location: index.php?status=not_found");
                    exit();
                }
            } else{
                echo "Ops! Algo deu errado. Tente novamente mais tarde.";
            }

            mysqli_stmt_close($stmt);
        }
    } else {
        header("location: index.php");
        exit();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Editar Produto #<?php echo htmlspecialchars($id_produto); ?></h2>
</div>

<p>Altere os dados do produto e clique em Salvar para atualizar o registro.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <input type="hidden" name="id_produto" value="<?php echo htmlspecialchars($id_produto); ?>">

    <div class="mb-3">
        <label for="nome_produto" class="form-label">Nome do Produto (*)</label>
        <input type="text" name="nome_produto" id="nome_produto" class="form-control <?php echo (!empty($nome_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($nome_produto); ?>" required>
        <div class="invalid-feedback"><?php echo $nome_err; ?></div>
    </div>

    <div class="mb-3">
        <label for="descricao" class="form-label">Descrição</label>
        <textarea name="descricao" id="descricao" class="form-control" rows="3"><?php echo htmlspecialchars($descricao); ?></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="categoria" class="form-label">Categoria</label>
            <input type="text" name="categoria" id="categoria" class="form-control" value="<?php echo htmlspecialchars($categoria); ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label for="fornecedor_id" class="form-label">Fornecedor (*)</label>
            <select name="fornecedor_id" id="fornecedor_id" class="form-select <?php echo (!empty($fornecedor_err)) ? 'is-invalid' : ''; ?>" required>
                <option value="">Selecione o Fornecedor</option>
                <?php foreach ($fornecedores as $fornecedor): ?>
                    <option value="<?php echo $fornecedor['id_fornecedor']; ?>"
                        <?php echo ($fornecedor['id_fornecedor'] == $fornecedor_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($fornecedor['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback"><?php echo $fornecedor_err; ?></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="preco_unitario" class="form-label">Preço Unitário (R$) (*)</label>
            <input type="text" name="preco_unitario" id="preco_unitario" class="form-control <?php echo (!empty($preco_err)) ? 'is-invalid' : ''; ?>"
                value="<?php echo htmlspecialchars(str_replace('.', ',', $preco_unitario)); ?>" required>
            <div class="invalid-feedback"><?php echo $preco_err; ?></div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="quantidade_estoque" class="form-label">Quantidade em Estoque</label>
            <input type="number" class="form-control" value="<?php echo htmlspecialchars($quantidade_estoque); ?>" min="0" disabled>
            <div class="form-text">Para alterar o saldo, use o módulo de Movimentação.</div>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary me-2">
        <i class="fas fa-sync-alt me-1"></i> Salvar Alterações
    </button>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-times me-1"></i> Cancelar
    </a>
</form>

<?php
require_once '../includes/footer.php';
?>