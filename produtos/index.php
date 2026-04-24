<?php
require_once '../includes/header.php';

function bind_dynamic_params(mysqli_stmt $stmt, string $types, array $params): bool
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

$opcoes_itens_por_pagina = [10, 25, 50, 100];
$itens_por_pagina = isset($_GET['itens_por_pagina']) ? (int) $_GET['itens_por_pagina'] : 10;
if (!in_array($itens_por_pagina, $opcoes_itens_por_pagina, true)) {
    $itens_por_pagina = 10;
}
$pagina_atual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$pagina_atual = $pagina_atual > 0 ? $pagina_atual : 1;

$busca = isset($_GET['busca']) ? trim((string) $_GET['busca']) : '';
$categoria_filtro = isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : '';
$fornecedor_filtro = isset($_GET['fornecedor_id']) ? (int) $_GET['fornecedor_id'] : 0;
$status_estoque = isset($_GET['status_estoque']) ? (string) $_GET['status_estoque'] : 'todos';
$status_validos = ['todos', 'em_falta', 'abaixo_minimo', 'normal'];
if (!in_array($status_estoque, $status_validos, true)) {
    $status_estoque = 'todos';
}

$fornecedores = [];
$sql_fornecedores = "SELECT id_fornecedor, nome FROM fornecedores ORDER BY nome ASC";
$result_fornecedores = mysqli_query($link, $sql_fornecedores);
if ($result_fornecedores) {
    while ($row_fornecedor = mysqli_fetch_assoc($result_fornecedores)) {
        $fornecedores[] = $row_fornecedor;
    }
    mysqli_free_result($result_fornecedores);
}

$where_clauses = [];
$where_types = '';
$where_params = [];

if ($busca !== '') {
    $where_clauses[] = "p.nome_produto LIKE ?";
    $where_types .= 's';
    $busca_like = '%' . $busca . '%';
    $where_params[] = $busca_like;
}

if ($categoria_filtro !== '') {
    $where_clauses[] = "p.categoria LIKE ?";
    $where_types .= 's';
    $where_params[] = '%' . $categoria_filtro . '%';
}

if ($fornecedor_filtro > 0) {
    $where_clauses[] = "p.fornecedor_id = ?";
    $where_types .= 'i';
    $where_params[] = $fornecedor_filtro;
}

if ($status_estoque === 'em_falta') {
    $where_clauses[] = "p.quantidade_estoque = 0";
} elseif ($status_estoque === 'abaixo_minimo') {
    $where_clauses[] = "p.quantidade_estoque > 0 AND p.quantidade_estoque <= p.estoque_minimo";
} elseif ($status_estoque === 'normal') {
    $where_clauses[] = "p.quantidade_estoque > p.estoque_minimo";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$sql_total = "SELECT COUNT(*) AS total
    FROM produtos p
    JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor" . $where_sql;
$stmt_total = mysqli_prepare($link, $sql_total);
if (!$stmt_total) {
    die("Erro ao preparar a contagem de produtos: " . mysqli_error($link));
}
bind_dynamic_params($stmt_total, $where_types, $where_params);
mysqli_stmt_execute($stmt_total);
$result_total = mysqli_stmt_get_result($stmt_total);
$total_registros = (int) mysqli_fetch_assoc($result_total)['total'];
mysqli_free_result($result_total);
mysqli_stmt_close($stmt_total);

$total_paginas = (int) ceil($total_registros / $itens_por_pagina);
$total_paginas = $total_paginas > 0 ? $total_paginas : 1;

if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql = "SELECT p.id_produto, p.nome_produto, p.categoria, p.preco_unitario, p.quantidade_estoque, p.estoque_minimo, f.nome AS nome_fornecedor
        FROM produtos p
        JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
        {$where_sql}
        ORDER BY p.id_produto DESC
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    die("Erro ao preparar listagem de produtos: " . mysqli_error($link));
}
$list_types = $where_types . 'ii';
$list_params = array_merge($where_params, [$itens_por_pagina, $offset]);
bind_dynamic_params($stmt, $list_types, $list_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    die("Erro ao consultar produtos: " . mysqli_error($link));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gerenciamento de Produtos</h2>
    <a href="novo.php" class="btn btn-success">
        <i class="fas fa-plus me-1"></i> Novo Produto
    </a>
</div>

<?php
if(isset($_GET['status'])){
    $status = $_GET['status'];
    if($status == 'success_create'){
        echo '<div class="alert alert-success">Produto criado com sucesso!</div>';
    } else if($status == 'success_edit'){
        echo '<div class="alert alert-success">Produto atualizado com sucesso!</div>';
    } else if($status == 'success_delete'){
        echo '<div class="alert alert-warning">Produto excluído com sucesso!</div>';
    }
}
?>

<p>Produtos cadastrados no estoque.</p>
<form action="" method="get" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-4">
        <label for="busca" class="form-label mb-1 small">Produtos</label>
        <input type="text" id="busca" name="busca" class="form-control form-control-sm" placeholder="Nome do produto" value="<?php echo htmlspecialchars($busca); ?>">
    </div>
    <div class="col-12 col-md-3">
        <label for="categoria" class="form-label mb-1 small">Categoria</label>
        <input type="text" id="categoria" name="categoria" class="form-control form-control-sm" value="<?php echo htmlspecialchars($categoria_filtro); ?>">
    </div>
    <div class="col-12 col-md-2">
        <label for="fornecedor_id" class="form-label mb-1 small">Fornecedor</label>
        <select id="fornecedor_id" name="fornecedor_id" class="form-select form-select-sm filtro-fornecedor-select">
            <option value="">Todos</option>
            <?php foreach ($fornecedores as $fornecedor): ?>
                <option value="<?php echo (int) $fornecedor['id_fornecedor']; ?>" <?php echo $fornecedor_filtro === (int) $fornecedor['id_fornecedor'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($fornecedor['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-2">
        <label for="status_estoque" class="form-label mb-1 small">Status Estoque</label>
        <select id="status_estoque" name="status_estoque" class="form-select form-select-sm">
            <option value="todos" <?php echo $status_estoque === 'todos' ? 'selected' : ''; ?>>Todos</option>
            <option value="em_falta" <?php echo $status_estoque === 'em_falta' ? 'selected' : ''; ?>>Em falta</option>
            <option value="abaixo_minimo" <?php echo $status_estoque === 'abaixo_minimo' ? 'selected' : ''; ?>>Abaixo mínimo</option>
            <option value="normal" <?php echo $status_estoque === 'normal' ? 'selected' : ''; ?>>Normal</option>
        </select>
    </div>
    <div class="col-12 col-md-1 d-grid">
        <input type="hidden" name="itens_por_pagina" value="<?php echo (int) $itens_por_pagina; ?>">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-12">
        <a href="index.php" class="btn btn-link btn-sm px-0">Limpar filtros</a>
    </div>
</form>
<style>
    .select2-container {
        width: 100% !important;
    }
    .filtro-fornecedor-select + .select2-container--default .select2-selection--single {
        height: 43.4px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        display: flex;
        align-items: center;
    }
    .filtro-fornecedor-select + .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 41.4px;
        color: #0f172a;
        padding-left: 12px;
    }
    .filtro-fornecedor-select + .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 43.4px;
    }
    .select2-dropdown {
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        overflow: hidden;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 6px 8px;
    }
</style>
<script>
    (function () {
        window.addEventListener('load', function () {
            if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
            var $fornecedor = jQuery('#fornecedor_id');
            if (!$fornecedor.length) return;
            $fornecedor.select2({
                width: '100%',
                placeholder: 'Todos',
                allowClear: true,
                minimumResultsForSearch: 0
            });
        });
    })();
</script>

<?php
if(mysqli_num_rows($result) > 0):
?>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome do Produto</th>
                <th>Categoria</th>
                <th>Preço Unitário</th>
                <th>Estoque</th>
                <th>Mínimo</th>
                <th>Fornecedor</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while($row = mysqli_fetch_assoc($result)):
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id_produto']); ?></td>
                <td><?php echo htmlspecialchars($row['nome_produto']); ?></td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td>R$ <?php echo number_format($row['preco_unitario'], 2, ',', '.'); ?></td>
                <td>
                    <span class="badge
                        <?php
                        if ((int) $row['quantidade_estoque'] === 0) echo 'bg-danger';
                        elseif ((int) $row['quantidade_estoque'] <= (int) $row['estoque_minimo']) echo 'bg-warning text-dark';
                        else echo 'bg-success';
                        ?>">
                        <?php echo htmlspecialchars($row['quantidade_estoque']); ?>
                    </span>
                </td>
                <td><?php echo (int) $row['estoque_minimo']; ?></td>
                <td><?php echo htmlspecialchars($row['nome_fornecedor']); ?></td>
                <td>
                    <a href="editar.php?id=<?php echo $row['id_produto']; ?>" class="btn btn-sm btn-primary" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="excluir.php?id=<?php echo $row['id_produto']; ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir o produto <?php echo addslashes($row['nome_produto']); ?>?');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
    $query_params = $_GET;
    unset($query_params['pagina']);
?>
<nav aria-label="Paginação de produtos" class="mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form action="" method="get" class="d-flex align-items-center gap-2 mb-0">
            <label for="itens_por_pagina" class="form-label mb-0 small text-muted text-nowrap">Registros por página:</label>
            <?php
                foreach ($_GET as $param => $valor) {
                    if ($param !== 'itens_por_pagina' && $param !== 'pagina') {
                        echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($valor) . '">';
                    }
                }
            ?>
            <select name="itens_por_pagina" id="itens_por_pagina" class="form-select form-select-sm" style="min-width: 96px;" onchange="this.form.submit()">
                <?php foreach ($opcoes_itens_por_pagina as $opcao): ?>
                    <option value="<?php echo $opcao; ?>" <?php echo $opcao === $itens_por_pagina ? 'selected' : ''; ?>>
                        <?php echo $opcao; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <ul class="pagination justify-content-end mb-0 flex-wrap">
            <?php
                $pagina_anterior = $pagina_atual - 1;
                $pagina_proxima = $pagina_atual + 1;
                $url_anterior = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina_anterior]));
                $url_proxima = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina_proxima]));
            ?>
            <li class="page-item <?php echo $pagina_atual <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $pagina_atual <= 1 ? '#' : $url_anterior; ?>" aria-label="Página anterior">Anterior</a>
            </li>

            <?php
                $janela = 3;
                $compacto_ate = 5;
                if ($total_paginas <= $compacto_ate):
                    for ($pagina = 1; $pagina <= $total_paginas; $pagina++):
                        $url_pagina = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina]));
            ?>
            <li class="page-item <?php echo $pagina === $pagina_atual ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo $url_pagina; ?>"><?php echo $pagina; ?></a>
            </li>
            <?php
                    endfor;
                else:
                    $metade = intdiv($janela, 2);
                    $inicio_janela = $pagina_atual - $metade;
                    $fim_janela = $pagina_atual + ($janela - $metade - 1);

                    if ($inicio_janela < 1) {
                        $inicio_janela = 1;
                        $fim_janela = $janela;
                    }
                    if ($fim_janela > $total_paginas) {
                        $fim_janela = $total_paginas;
                        $inicio_janela = $total_paginas - $janela + 1;
                    }

                    if ($inicio_janela > 1):
                        $url_pagina = '?' . http_build_query(array_merge($query_params, ['pagina' => 1]));
            ?>
            <li class="page-item <?php echo $pagina_atual === 1 ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo $url_pagina; ?>">1</a>
            </li>
            <?php if ($inicio_janela > 2): ?>
            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($pagina = $inicio_janela; $pagina <= $fim_janela; $pagina++): ?>
                <?php $url_pagina = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina])); ?>
                <li class="page-item <?php echo $pagina === $pagina_atual ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $url_pagina; ?>"><?php echo $pagina; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($fim_janela < $total_paginas): ?>
                <?php if ($fim_janela < $total_paginas - 1): ?>
                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
                <?php $url_pagina = '?' . http_build_query(array_merge($query_params, ['pagina' => $total_paginas])); ?>
                <li class="page-item <?php echo $pagina_atual === $total_paginas ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $url_pagina; ?>"><?php echo $total_paginas; ?></a>
                </li>
            <?php endif; ?>
            <?php endif; ?>

            <li class="page-item <?php echo $pagina_atual >= $total_paginas ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $pagina_atual >= $total_paginas ? '#' : $url_proxima; ?>" aria-label="Próxima página">Próxima</a>
            </li>
        </ul>
    </div>
</nav>
<?php
mysqli_free_result($result);
else:
?>
<div class="alert alert-info" role="alert">
    Nenhum produto encontrado para os filtros informados.
</div>
<?php endif; ?>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt);
}
?>

<?php require_once '../includes/footer.php'; ?>