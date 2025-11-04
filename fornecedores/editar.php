<?php
require_once '../includes/header.php';

$nome = $telefone = $email = $uf = $cep = "";
$nome_err = $uf_err = "";
$id_fornecedor = 0;

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $id_fornecedor = $_POST["id_fornecedor"];

    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira o nome do fornecedor.";
    } else{
        $nome = trim($_POST["nome"]);
    }

    if(empty(trim($_POST["uf"]))){
        $uf_err = "Por favor, insira a UF (Estado).";
    } else{
        $uf = trim($_POST["uf"]);
    }

    $telefone = trim($_POST["telefone"]);
    $email = trim($_POST["email"]);
    $cep = trim($_POST["cep"]);

    $sql_original = "SELECT nome, telefone, email, UF, cep FROM fornecedores WHERE id_fornecedor = ?";
    if ($stmt_orig = mysqli_prepare($link, $sql_original)) {
        mysqli_stmt_bind_param($stmt_orig, "i", $param_id_forn);
        $param_id_forn = $id_fornecedor;
        mysqli_stmt_execute($stmt_orig);
        $result_orig = mysqli_stmt_get_result($stmt_orig);
        $row_orig = mysqli_fetch_assoc($result_orig);

        $original_nome = $row_orig["nome"];
        $original_telefone = $row_orig["telefone"];
        $original_email = $row_orig["email"];
        $original_uf = $row_orig["UF"];
        $original_cep = $row_orig["cep"];
        mysqli_stmt_close($stmt_orig);
    }

    if(empty($nome_err) && empty($uf_err)){

        $sql = "UPDATE fornecedores SET nome = ?, telefone = ?, email = ?, UF = ?, cep = ? WHERE id_fornecedor = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssi", $param_nome, $param_telefone, $param_email, $param_uf, $param_cep, $param_id);

            $param_nome = $nome;
            $param_telefone = $telefone;
            $param_email = $email;
            $param_uf = $uf;
            $param_cep = $cep;
            $param_id = $id_fornecedor;

            if(mysqli_stmt_execute($stmt)){
                // Início auditoria
                $modificacoes = [];

                if ($nome != $original_nome) { $modificacoes[] = "Nome: '{$original_nome}' -> '{$nome}'"; }
                if ($telefone != $original_telefone) { $modificacoes[] = "Telefone: '{$original_telefone}' -> '{$telefone}'"; }
                if ($email != $original_email) { $modificacoes[] = "Email: '{$original_email}' -> '{$email}'"; }
                if ($uf != $original_uf) { $modificacoes[] = "UF: '{$original_uf}' -> '{$uf}'"; }
                if ($cep != $original_cep) { $modificacoes[] = "CEP: '{$original_cep}' -> '{$cep}'"; }

                $acao = "Editou Fornecedor";
                if (count($modificacoes) > 0) {
                    $detalhes = "Fornecedor ID {$id_fornecedor}. Alterações: " . implode(" | ", $modificacoes);
                } else {
                    $detalhes = "Fornecedor ID {$id_fornecedor}. Nenhuma alteração significativa detectada.";
                }

                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);
                // Fim auditoria

                header("location: index.php?status=success_edit");
                exit();
            } else{
                echo '<div class="alert alert-danger">Erro ao tentar atualizar o fornecedor. Tente novamente mais tarde.</div>';
            }

            mysqli_stmt_close($stmt);
        }
    }

} else {

    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

        $id_fornecedor = trim($_GET["id"]);

        $sql = "SELECT nome, telefone, email, UF, cep FROM fornecedores WHERE id_fornecedor = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);

            $param_id = $id_fornecedor;

            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                    $nome = $row["nome"];
                    $telefone = $row["telefone"];
                    $email = $row["email"];
                    $uf = $row["UF"];
                    $cep = $row["cep"];
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
mysqli_close($link);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Editar Fornecedor #<?php echo htmlspecialchars($id_fornecedor); ?></h2>
</div>

<p>Altere os dados do fornecedor e clique em Salvar para atualizar o registro.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <input type="hidden" name="id_fornecedor" value="<?php echo htmlspecialchars($id_fornecedor); ?>">

    <div class="mb-3">
        <label for="nome" class="form-label">Nome da Empresa (*)</label>
        <input type="text" name="nome" id="nome" class="form-control <?php echo (!empty($nome_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($nome); ?>" required>
        <div class="invalid-feedback"><?php echo $nome_err; ?></div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo htmlspecialchars($telefone); ?>">
        </div>

        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
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
            <input type="text" name="cep" id="cep" class="form-control" value="<?php echo htmlspecialchars($cep); ?>">
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

<?php require_once '../includes/footer.php'; ?>