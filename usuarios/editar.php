<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "index.php?status=permission_denied");
    exit();
}

$id_usuario = $nome = $email = $cargo = $nova_senha = "";
$ativo = 1;
$nome_err = $email_err = $cargo_err = $senha_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $id_usuario = $_POST["id_usuario"];

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

    $ativo = (isset($_POST["ativo"]) && $_POST["ativo"] == 1) ? 1 : 0;

    $nova_senha = trim($_POST["nova_senha"]);
    if (!empty($nova_senha)) {
        if(strlen($nova_senha) > 10){
            $senha_err = "A senha não pode exceder 10 caracteres.";
        }
    }

    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, insira o e-mail.";
    } else{
        $email = trim($_POST["email"]);

        $sql = "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario <> ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "si", $param_email, $param_id);
            $param_email = $email;
            $param_id = $id_usuario;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Este e-mail já está em uso por outro usuário.";
                }
            } else{
                echo '<div class="alert alert-danger">Erro de banco de dados ao verificar e-mail.</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }

    $sql_original = "SELECT nome, email, cargo, ativo FROM usuarios WHERE id_usuario = ?";
    if ($stmt_orig = mysqli_prepare($link, $sql_original)) {
        mysqli_stmt_bind_param($stmt_orig, "i", $param_id_forn);
        $param_id_forn = $id_usuario;
        mysqli_stmt_execute($stmt_orig);
        $result_orig = mysqli_stmt_get_result($stmt_orig);
        $row_orig = mysqli_fetch_assoc($result_orig);

        $original_nome = $row_orig["nome"];
        $original_email = $row_orig["email"];
        $original_cargo = $row_orig["cargo"];
        $original_ativo = $row_orig["ativo"];
        mysqli_stmt_close($stmt_orig);
    }

    if(empty($nome_err) && empty($email_err) && empty($cargo_err) && empty($senha_err)){

        $sql = "UPDATE usuarios SET nome = ?, email = ?, cargo = ?, ativo = ?";

        if (!empty($nova_senha)) {
            $sql .= ", senha = ?";
        }
        $sql .= " WHERE id_usuario = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            if (!empty($nova_senha)) {
                mysqli_stmt_bind_param($stmt, "sssisi", $param_nome, $param_email, $param_cargo, $param_ativo, $param_senha, $param_id);
                $param_senha = password_hash($nova_senha, PASSWORD_DEFAULT);
            } else {
                mysqli_stmt_bind_param($stmt, "sssii", $param_nome, $param_email, $param_cargo, $param_ativo, $param_id);
            }

            $param_nome = $nome;
            $param_email = $email;
            $param_cargo = $cargo;
            $param_ativo = $ativo;
            $param_id = $id_usuario;

            if(mysqli_stmt_execute($stmt)){
                // Início auditoria
                $modificacoes = [];

                if ($nome != $original_nome) { $modificacoes[] = "Nome: '{$original_nome}' -> '{$nome}'"; }
                if ($email != $original_email) { $modificacoes[] = "Email: '{$original_email}' -> '{$email}'"; }
                if ($cargo != $original_cargo) { $modificacoes[] = "Cargo: '{$original_cargo}' -> '{$cargo}'"; }
                if ($ativo != $original_ativo) {
                    $status_orig = ($original_ativo == 1) ? 'Ativo' : 'Inativo';
                    $status_novo = ($ativo == 1) ? 'Ativo' : 'Inativo';
                    $modificacoes[] = "Status: '{$status_orig}' -> '{$status_novo}'";
                }
                if (!empty($nova_senha)) { $modificacoes[] = "Senha alterada."; }

                $acao = "Editou Usuário";
                if (count($modificacoes) > 0) {
                    $detalhes = "Usuário ID {$id_usuario}. Alterações: " . implode(" | ", $modificacoes);
                } else {
                    $detalhes = "Usuário ID {$id_usuario}. Nenhuma alteração significativa detectada.";
                }

                registrar_acao($link, $acao, $detalhes, $_SESSION['id_usuario']);
                // Fim auditoria

                header("location: index.php?status=success_edit");
                exit();
            } else{
                echo '<div class="alert alert-danger">Erro ao tentar atualizar o usuário. Tente novamente mais tarde.</div>';
            }

            mysqli_stmt_close($stmt);
        }
    }

} else {

    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){

        $id_usuario = trim($_GET["id"]);

        $sql = "SELECT nome, email, cargo, ativo FROM usuarios WHERE id_usuario = ?";

        if($stmt = mysqli_prepare($link, $sql)){

            mysqli_stmt_bind_param($stmt, "i", $param_id);

            $param_id = $id_usuario;

            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                    $nome = $row["nome"];
                    $email = $row["email"];
                    $cargo = $row["cargo"];
                    $ativo = $row["ativo"];
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
    <h2>Editar Usuário #<?php echo htmlspecialchars($id_usuario); ?></h2>
</div>

<p>Altere as informações do usuário. Deixe o campo Senha em branco para manter a senha atual.</p>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

    <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($id_usuario); ?>">

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
            <label for="cargo" class="form-label">Cargo (*)</label>
            <select name="cargo" id="cargo" class="form-select <?php echo (!empty($cargo_err)) ? 'is-invalid' : ''; ?>" required>
                <option value="">Selecione o Cargo</option>
                <option value="Administrador" <?php echo ($cargo == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                <option value="Funcionário" <?php echo ($cargo == 'Funcionário') ? 'selected' : ''; ?>>Funcionário</option>
            </select>
            <div class="invalid-feedback"><?php echo $cargo_err; ?></div>
        </div>
    </div>

    <div class="mb-3">
        <label for="nova_senha" class="form-label">Nova Senha (Opcional, Máx. 10 caracteres)</label>
        <input type="password" name="nova_senha" id="nova_senha" class="form-control <?php echo (!empty($senha_err)) ? 'is-invalid' : ''; ?>" maxlength="10">
        <div class="form-text">Preencha apenas se desejar alterar a senha.</div>
        <div class="invalid-feedback"><?php echo $senha_err; ?></div>
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" <?php echo ($ativo == 1) ? 'checked' : ''; ?>

        <?php if ($id_usuario == $_SESSION['id_usuario']): ?>
            disabled title="Você não pode inativar sua própria conta."
        <?php endif; ?>
        >
        <label class="form-check-label" for="ativo">Usuário Ativo (Permite acesso ao sistema)</label>

        <?php if ($id_usuario == $_SESSION['id_usuario']): ?>
            <input type="hidden" name="ativo" value="<?php echo $ativo; ?>">
        <?php endif; ?>
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