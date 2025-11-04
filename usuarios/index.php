<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "/index.php?status=permission_denied");
    exit();
}

$sql = "SELECT id_usuario, nome, email, cargo, ativo FROM usuarios ORDER BY id_usuario ASC";

$result = mysqli_query($link, $sql);

if (!$result) {
    die("Erro ao consultar usuários: " . mysqli_error($link));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gerenciamento de Usuários</h2>
    <a href="novo.php" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Novo Usuário
    </a>
</div>

<?php
if(isset($_GET['status'])){
    $status = $_GET['status'];
    if($status == 'success_create'){
        echo '<div class="alert alert-success">Usuário criado com sucesso!</div>';
    } else if($status == 'success_edit'){
        echo '<div class="alert alert-success">Usuário atualizado com sucesso!</div>';
    } else if($status == 'success_delete'){
        echo '<div class="alert alert-warning">Usuário inativado com sucesso!</div>';
    } else if($status == 'not_found'){
        echo '<div class="alert alert-danger">Erro: Usuário não encontrado ou ID inválido.</div>';
    } else if($status == 'cannot_self_delete'){
        echo '<div class="alert alert-danger"><b>Atenção:</b> Você não pode inativar sua própria conta enquanto estiver logado.</div>';
    }
}
?>

<p>Informações das pessoas que têm acesso ao sistema (Administradores e Funcionários).</p>

<?php
if(mysqli_num_rows($result) > 0):
?>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Cargo</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id_usuario']); ?></td>
                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <span class="badge <?php echo ($row['cargo'] == 'Administrador') ? 'bg-primary' : 'bg-info text-dark'; ?>">
                        <?php echo htmlspecialchars($row['cargo']); ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?php echo ($row['ativo'] == 1) ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo ($row['ativo'] == 1) ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </td>
                <td>
                    <a href="editar.php?id=<?php echo $row['id_usuario']; ?>" class="btn btn-sm btn-primary" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>

                    <?php if ($row['id_usuario'] != $_SESSION['id_usuario']): ?>
                        <?php if ($row['ativo'] == 1): ?>
                            <a href="excluir.php?id=<?php echo $row['id_usuario']; ?>" class="btn btn-sm btn-danger" title="Inativar Usuário" onclick="return confirm('Tem certeza que deseja INATIVAR o usuário <?php echo addslashes($row['nome']); ?>? Ele perderá o acesso ao sistema.');">
                                <i class="fas fa-user-times"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
mysqli_free_result($result);
else:
?>
<div class="alert alert-info" role="alert">
    Nenhum usuário encontrado no sistema.
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>