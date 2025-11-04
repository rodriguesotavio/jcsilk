<?php
session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}

require_once "./includes/conexao.php";
require_once "./includes/log_helper.php";

$email = $senha = "";
$login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if(empty(trim($_POST["email"])) || empty(trim($_POST["senha"]))){
        $login_err = "Por favor, preencha o e-mail e a senha.";
    } else {
        $email = trim($_POST["email"]);
        $senha = trim($_POST["senha"]);
    }

    if(empty($login_err)){
        $sql = "SELECT id_usuario, nome, email, senha, cargo, ativo FROM usuarios WHERE email = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            $param_email = $email;

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    mysqli_stmt_bind_result($stmt, $id_usuario, $nome, $email_db, $senha_db, $cargo, $ativo);

                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($senha, $senha_db)){
                            if($ativo == 1){
                                session_start();

                                $_SESSION["loggedin"] = true;
                                $_SESSION["id_usuario"] = $id_usuario;
                                $_SESSION["nome"] = $nome;
                                $_SESSION["cargo"] = $cargo;

                                $acao = "Login";
                                $detalhes = "Usuário '{$nome}' (ID: {$id_usuario}) logou no sistema.";
                                registrar_acao($link, $acao, $detalhes, $id_usuario);

                                header("location: index.php");
                            } else {
                                $login_err = "Sua conta está inativa. Contate o administrador.";
                            }
                        } else{
                            $login_err = "E-mail ou senha inválidos.";
                        }
                    }
                } else{
                    $login_err = "E-mail ou senha inválidos.";
                }
            } else{
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Silk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin-top: 10vh;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 login-container">
            <h2 class="text-center mb-4">Login no Sistema</h2>

            <?php
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail:</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" name="senha" id="senha" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>