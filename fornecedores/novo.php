<?php
require_once '../includes/header.php';

$nome = $telefone = $email = $uf = $cep = "";
$nome_err = $telefone_err = $email_err = $uf_err = $cep_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira o nome do fornecedor.";
    } else{
        $nome = trim($_POST["nome"]);
    }

    $telefone = trim($_POST["telefone"]);
    $email = trim($_POST["email"]);

    if(empty(trim($_POST["uf"]))){
        $uf_err = "Por favor, insira a UF (Estado).";
    } else{
        $uf = trim($_POST["uf"]);
    }

    $cep = trim($_POST["cep"]);

    if(empty($nome_err) && empty($uf_err)){

        $sql = "INSERT INTO fornecedores (nome, telefone, email, UF, cep) VALUES (?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssss", $param_nome, $param_telefone, $param_email, $param_uf, $param_cep);

            $param_nome = $nome;
            $param_telefone = $telefone;
            $param_email = $email;
            $param_uf = $uf;
            $param_cep = $cep;

            if(mysqli_stmt_execute($stmt)){
                $id_fornecedor_inserido = mysqli_insert_id($link);
                $acao = "Adicionou Fornecedor";
                $detalhes = "Fornecedor: " . $nome . " (ID: " . $id_fornecedor_inserido . "). UF: " . $uf;
                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);

                header("location: index.php?status=success_create");
                exit();
            } else{
                echo '<div class="alert alert-danger">Erro ao tentar inserir o fornecedor. Tente novamente mais tarde.</div>';
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Adicionar Novo Fornecedor</h2>
</div>

<p>Preencha este formulário para adicionar um novo fornecedor ao sistema.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <div class="mb-3">
        <label for="nome" class="form-label">Nome da Empresa (*)</label>
        <input type="text" name="nome" id="nome" class="form-control <?php echo (!empty($nome_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($nome); ?>" required>
        <div class="invalid-feedback"><?php echo $nome_err; ?></div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="text" name="telefone" id="telefone" class="form-control <?php echo (!empty($telefone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($telefone); ?>">
            <div class="invalid-feedback"><?php echo $telefone_err; ?></div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
            <div class="invalid-feedback"><?php echo $email_err; ?></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="uf" class="form-label">UF (Estado) (*)</label>
            <input type="text" name="uf" id="uf" maxlength="2" class="form-control <?php echo (!empty($uf_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($uf); ?>" required>
            <div class="invalid-feedback"><?php echo $uf_err; ?></div>
        </div>

        <div class="col-md-8 mb-3">
            <label for="cep" class="form-label">CEP</label>
            <input type="text" name="cep" id="cep" class="form-control <?php echo (!empty($cep_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($cep); ?>">
            <div class="invalid-feedback"><?php echo $cep_err; ?></div>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-success me-2">
        <i class="fas fa-save me-1"></i> Salvar Fornecedor
    </button>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-times me-1"></i> Cancelar
    </a>
</form>

<?php require_once '../includes/footer.php'; ?>