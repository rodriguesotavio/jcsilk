<?php
require_once '../includes/header.php';

function fornecedores_bind_dynamic(mysqli_stmt $stmt, string $types, array $params): bool
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
$uf_filtro = isset($_GET['uf']) ? trim((string) $_GET['uf']) : '';

$where_clauses = [];
$where_types = '';
$where_params = [];

if ($busca !== '') {
    $where_clauses[] = "(nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
    $where_types .= 'sss';
    $busca_like = '%' . $busca . '%';
    array_push($where_params, $busca_like, $busca_like, $busca_like);
}
if ($uf_filtro !== '') {
    $where_clauses[] = "UF = ?";
    $where_types .= 's';
    $where_params[] = strtoupper($uf_filtro);
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
$sql = "SELECT id_fornecedor, nome, telefone, email, UF, cep FROM fornecedores {$where_sql} ORDER BY id_fornecedor DESC";
$stmt = mysqli_prepare($link, $sql);
if ($stmt) {
    fornecedores_bind_dynamic($stmt, $where_types, $where_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

if (!$result) {
    die("Erro ao consultar fornecedores: " . mysqli_error($link));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gerenciamento de Fornecedores</h2>
    <a href="novo.php" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Novo Fornecedor
    </a>
</div>

<?php
if(isset($_GET['status'])){
    $status = $_GET['status'];
    if($status == 'success_create'){
        echo '<div class="alert alert-success">Fornecedor criado com sucesso!</div>';
    } else if($status == 'success_edit'){
        echo '<div class="alert alert-success">Fornecedor atualizado com sucesso!</div>';
    } else if($status == 'success_delete'){
        echo '<div class="alert alert-warning">Fornecedor excluído com sucesso!</div>';
    } else if($status == 'fk_error'){
        echo '<div class="alert alert-danger"><b>Atenção:</b> O fornecedor não pode ser excluído, pois existem produtos cadastrados vinculados a ele.</div>';
    } else if($status == 'general_error'){
        echo '<div class="alert alert-danger">Erro inesperado ao tentar excluir o fornecedor. Verifique o log ou tente novamente.</div>';
    }
}
?>

<p>Dados dos fornecedores.</p>
<form action="" method="get" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-5">
        <label for="busca" class="form-label mb-1 small">Busca</label>
        <input type="text" id="busca" name="busca" class="form-control form-control-sm" placeholder="Nome, e-mail ou telefone" value="<?php echo htmlspecialchars($busca); ?>">
    </div>
    <div class="col-12 col-md-2">
        <label for="uf" class="form-label mb-1 small">UF</label>
        <input type="text" id="uf" name="uf" class="form-control form-control-sm text-uppercase" maxlength="2" value="<?php echo htmlspecialchars($uf_filtro); ?>">
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
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>UF</th>
                <th>CEP</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id_fornecedor']); ?></td>
                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['UF']); ?></td>
                <td><?php echo htmlspecialchars($row['cep']); ?></td>
                <td>
                    <a href="editar.php?id=<?php echo $row['id_fornecedor']; ?>" class="btn btn-sm btn-primary" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="excluir.php?id=<?php echo $row['id_fornecedor']; ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este fornecedor?');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
mysqli_free_result($result);
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt);
}
mysqli_close($link);

else:
?>
<div class="alert alert-info" role="alert">
    Nenhum fornecedor encontrado no banco de dados.
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
