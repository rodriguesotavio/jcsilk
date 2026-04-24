<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "/index.php?status=permission_denied");
    exit();
}

function usuarios_bind_dynamic(mysqli_stmt $stmt, string $types, array $params): bool
{
    if ($types === '') {
        return true;
    }
    $bind_values = array_merge([$types], array_values($params));
    $refs = [];
    foreach ($bind_values as $k => $_) {
        $refs[$k] = &$bind_values[$k];
    }
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

$busca = isset($_GET['busca']) ? trim((string) $_GET['busca']) : '';
$cargo_filtro = isset($_GET['cargo']) ? trim((string) $_GET['cargo']) : '';
$status_filtro = isset($_GET['status_usuario']) ? (string) $_GET['status_usuario'] : 'todos';
$status_validos = ['todos', 'ativo', 'inativo'];
if (!in_array($status_filtro, $status_validos, true)) {
    $status_filtro = 'todos';
}

$where_clauses = [];
$where_types = '';
$where_params = [];

if ($busca !== '') {
    $where_clauses[] = "(nome LIKE ? OR email LIKE ?)";
    $where_types .= 'ss';
    $busca_like = '%' . $busca . '%';
    array_push($where_params, $busca_like, $busca_like);
}
if ($cargo_filtro !== '') {
    $where_clauses[] = "cargo = ?";
    $where_types .= 's';
    $where_params[] = $cargo_filtro;
}
if ($status_filtro === 'ativo') {
    $where_clauses[] = "ativo = 1";
} elseif ($status_filtro === 'inativo') {
    $where_clauses[] = "ativo = 0";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
$sql = "SELECT id_usuario, nome, email, cargo, ativo FROM usuarios {$where_sql} ORDER BY id_usuario ASC";
$stmt = mysqli_prepare($link, $sql);
if ($stmt) {
    usuarios_bind_dynamic($stmt, $where_types, $where_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

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
<form action="" method="get" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-5">
        <label for="busca" class="form-label mb-1 small">Busca</label>
        <input type="text" id="busca" name="busca" class="form-control form-control-sm" placeholder="Nome ou e-mail" value="<?php echo htmlspecialchars($busca); ?>">
    </div>
    <div class="col-12 col-md-3">
        <label for="cargo" class="form-label mb-1 small">Cargo</label>
        <select id="cargo" name="cargo" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="Administrador" <?php echo $cargo_filtro === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
            <option value="Funcionário" <?php echo $cargo_filtro === 'Funcionário' ? 'selected' : ''; ?>>Funcionário</option>
        </select>
    </div>
    <div class="col-12 col-md-2">
        <label for="status_usuario" class="form-label mb-1 small">Status</label>
        <select id="status_usuario" name="status_usuario" class="form-select form-select-sm">
            <option value="todos" <?php echo $status_filtro === 'todos' ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo $status_filtro === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
            <option value="inativo" <?php echo $status_filtro === 'inativo' ? 'selected' : ''; ?>>Inativo</option>
        </select>
    </div>
    <div class="col-12 col-md-1 d-grid">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-12">
        <a href="index.php" class="btn btn-link btn-sm px-0">Limpar filtros</a>
    </div>
</form>

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
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt);
}
require_once '../includes/footer.php';
?>
