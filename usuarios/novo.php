<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "index.php?status=permission_denied");
    exit();
}

$nome = $email = $senha = $cargo = "";
$nome_err = $email_err = $senha_err = $cargo_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["nome"]))){
        $nome_err = "Por favor, insira o nome do usuário.";
    } else{
        $nome = trim($_POST["nome"]);
    }

    if(empty(trim($_POST["cargo"]))){
        $cargo_err = "Por favor, selecione o cargo.";
    } else{
        $cargo = trim($_POST["cargo"]);
    }

    if(empty(trim($_POST["senha"]))){
        $senha_err = "Por favor, insira uma senha.";
    } elseif(strlen(trim($_POST["senha"])) > 10){
        $senha_err = "A senha não pode exceder 10 caracteres.";
    } else{
        $senha = trim($_POST["senha"]);
    }

    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira o e-mail.";
    } else{
        $email = trim($_POST["email"]);

        $sql = "SELECT id_usuario FROM usuarios WHERE email = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Este e-mail já está em uso.";
                }
            } else{
                echo '<div class="alert alert-danger">Erro de banco de dados ao verificar e-mail.</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }

    if(empty($nome_err) && empty($email_err) && empty($senha_err) && empty($cargo_err)){

        $ativo = 1;

        $sql = "INSERT INTO usuarios (nome, email, senha, cargo, ativo) VALUES (?, ?, ?, ?, ?)";

        if($stmt = mysqli_prepare($link, $sql)){

            mysqli_stmt_bind_param($stmt, "ssssi", $param_nome, $param_email, $param_senha, $param_cargo, $param_ativo);

            $param_nome = $nome;
            $param_email = $email;
            $param_senha = password_hash($senha, PASSWORD_DEFAULT);
            $param_cargo = $cargo;
            $param_ativo = $ativo;

            if(mysqli_stmt_execute($stmt)){
                $id_usuario_inserido = mysqli_insert_id($link);
                $acao = "Criou Usuário";
                $detalhes = "Usuário: " . $nome . " (ID: " . $id_usuario_inserido . "). Cargo: " . $cargo . ". Status: Ativo.";
                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);

                header("location: index.php?status=success_create");
                exit();
            } else{
                echo '<div class="alert alert-danger">Erro ao tentar inserir o usuário. Tente novamente mais tarde.</div>';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Adicionar Novo Usuário</h2>
</div>

<p>Preencha este formulário para conceder acesso a um novo usuário.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <div class="mb-3">
        <label for="nome" class="form-label">Nome Completo (*)</label>
        <input type="text" name="nome" id="nome" class="form-control <?php echo (!empty($nome_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($nome); ?>" required>
        <div class="invalid-feedback"><?php echo $nome_err; ?></div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">E-mail de Login (*)</label>
            <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
            <div class="invalid-feedback"><?php echo $email_err; ?></div>
        </div>

        <div class="col-md-6 mb-3">
            <label for="senha" class="form-label">Senha (Máx. 10 caracteres) (*)</label>
            <input type="password" name="senha" id="senha" class="form-control <?php echo (!empty($senha_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($senha); ?>" maxlength="10" required>
            <div class="invalid-feedback"><?php echo $senha_err; ?></div>
        </div>
    </div>

    <div class="mb-3">
        <label for="cargo" class="form-label">Cargo (*)</label>
        <select name="cargo" id="cargo" class="form-select <?php echo (!empty($cargo_err)) ? 'is-invalid' : ''; ?>" required>
            <option value="">Selecione o Cargo</option>
            <option value="Administrador" <?php echo ($cargo == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
            <option value="Funcionário" <?php echo ($cargo == 'Funcionário') ? 'selected' : ''; ?>>Funcionário</option>
        </select>
        <div class="invalid-feedback"><?php echo $cargo_err; ?></div>
    </div>

    <hr>
    <button type="submit" class="btn btn-success me-2">
        <i class="fas fa-user-plus me-1"></i> Criar Usuário
    </button>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-times me-1"></i> Cancelar
    </a>
</form>

<?php
require_once '../includes/footer.php';
?>