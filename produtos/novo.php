<?php
require_once '../includes/header.php';

$nome_produto = $descricao = $categoria = $preco_unitario = $fornecedor_id = "";
$estoque_minimo = 5;
$nome_err = $preco_err = $fornecedor_err = $estoque_minimo_err = "";

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
    if(empty(trim($_POST["nome_produto"]))){
        $nome_err = "Por favor, insira o nome do produto.";
    } else{
        $nome_produto = trim($_POST["nome_produto"]);
    }

    $valor_digitado = trim($_POST["preco_unitario"]);
    $sanitized_value = str_replace('.', '', $valor_digitado);
    $sanitized_value = str_replace(',', '.', $sanitized_value);

    if(empty($valor_digitado) || !is_numeric($sanitized_value)){
        $preco_err = "O preço deve ser um valor numérico válido.";
        $preco_unitario = $valor_digitado;
    } else{
        $preco_unitario = (float) $sanitized_value;
    }

    $quantidade_estoque = 0;

    if(empty(trim($_POST["fornecedor_id"]))){
        $fornecedor_err = "Por favor, selecione um fornecedor.";
    } else{
        $fornecedor_id = trim($_POST["fornecedor_id"]);
    }

    $descricao = trim($_POST["descricao"]);
    $categoria = trim($_POST["categoria"]);
    $estoque_minimo = isset($_POST["estoque_minimo"]) ? (int) $_POST["estoque_minimo"] : 5;
    if ($estoque_minimo < 0) {
        $estoque_minimo_err = "O estoque mínimo deve ser igual ou maior que zero.";
    }

    if(empty($nome_err) && empty($preco_err) && empty($fornecedor_err) && empty($estoque_minimo_err)){

        $sql = "INSERT INTO produtos (nome_produto, descricao, categoria, preco_unitario, quantidade_estoque, estoque_minimo, fornecedor_id) VALUES (?, ?, ?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssdiii", $param_nome, $param_descricao, $param_categoria, $param_preco, $param_quantidade, $param_estoque_minimo, $param_fornecedor_id);

            $param_nome = $nome_produto;
            $param_descricao = $descricao;
            $param_categoria = $categoria;
            $param_preco = $preco_unitario;
            $param_quantidade = $quantidade_estoque;
            $param_estoque_minimo = $estoque_minimo;
            $param_fornecedor_id = $fornecedor_id;

            if(mysqli_stmt_execute($stmt)){
                $id_produto_inserido = mysqli_insert_id($link);
                $acao = "Adicionou Produto";
                $detalhes = "Produto: " . $nome_produto . " (ID: " . $id_produto_inserido . "). Categoria: " . $categoria;
                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);

                header("location: index.php?status=success_create");
                exit();
            } else{
                echo '<div class="alert alert-danger">Erro ao tentar inserir o produto. Tente novamente mais tarde.</div>';
            }

            mysqli_stmt_close($stmt);
        }
    }

}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Adicionar Novo Produto</h2>
</div>

<p>Preencha este formulário para adicionar um novo item ao estoque.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

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
            <input type="text" name="preco_unitario" id="preco_unitario" class="form-control <?php echo (!empty($preco_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars(str_replace('.', ',', $preco_unitario)); ?>" required>
            <div class="invalid-feedback"><?php echo $preco_err; ?></div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="quantidade_estoque" class="form-label">Quantidade em Estoque Atual</label>
            <input type="number" class="form-control" value="0" min="0" disabled>
            <div class="form-text">A quantidade inicial é 0 e deve ser ajustada via Movimentação de Estoque.</div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="estoque_minimo" class="form-label">Estoque Mínimo (*)</label>
            <input type="number" name="estoque_minimo" id="estoque_minimo" min="0" class="form-control <?php echo (!empty($estoque_minimo_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars((string) $estoque_minimo); ?>" required>
            <div class="invalid-feedback"><?php echo $estoque_minimo_err; ?></div>
            <div class="form-text">Usado para alertas automáticos de reposição no dashboard.</div>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-success me-2">
        <i class="fas fa-save me-1"></i> Salvar Produto
    </button>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-times me-1"></i> Cancelar
    </a>
</form>

<?php
require_once '../includes/footer.php';
?>