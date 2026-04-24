<?php
require_once '../includes/header.php';

$opcoes_itens_por_pagina = [10, 25, 50, 100];
$itens_por_pagina = isset($_GET['itens_por_pagina']) ? (int) $_GET['itens_por_pagina'] : 10;
if (!in_array($itens_por_pagina, $opcoes_itens_por_pagina, true)) {
    $itens_por_pagina = 10;
}
$pagina_atual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$pagina_atual = $pagina_atual > 0 ? $pagina_atual : 1;

$sql_total = "SELECT COUNT(*) AS total FROM produtos";
$result_total = mysqli_query($link, $sql_total);

if (!$result_total) {
    die("Erro ao contar produtos: " . mysqli_error($link));
}

$total_registros = (int) mysqli_fetch_assoc($result_total)['total'];
mysqli_free_result($result_total);
$total_paginas = (int) ceil($total_registros / $itens_por_pagina);
$total_paginas = $total_paginas > 0 ? $total_paginas : 1;

if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql = "SELECT p.id_produto, p.nome_produto, p.categoria, p.preco_unitario, p.quantidade_estoque, f.nome AS nome_fornecedor
        FROM produtos p
        JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
        ORDER BY p.id_produto DESC
        LIMIT {$itens_por_pagina} OFFSET {$offset}";

$result = mysqli_query($link, $sql);

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
                        if ($row['quantidade_estoque'] <= 5) echo 'bg-danger';
                        elseif ($row['quantidade_estoque'] <= 20) echo 'bg-warning text-dark';
                        else echo 'bg-success';
                        ?>">
                        <?php echo htmlspecialchars($row['quantidade_estoque']); ?>
                    </span>
                </td>
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
        <ul class="pagination justify-content-end mb-0">
            <?php
                $pagina_anterior = $pagina_atual - 1;
                $pagina_proxima = $pagina_atual + 1;
                $url_anterior = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina_anterior]));
                $url_proxima = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina_proxima]));
            ?>
            <li class="page-item <?php echo $pagina_atual <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo $pagina_atual <= 1 ? '#' : $url_anterior; ?>" aria-label="Página anterior">Anterior</a>
            </li>

            <?php for ($pagina = 1; $pagina <= $total_paginas; $pagina++): ?>
                <?php $url_pagina = '?' . http_build_query(array_merge($query_params, ['pagina' => $pagina])); ?>
                <li class="page-item <?php echo $pagina === $pagina_atual ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo $url_pagina; ?>"><?php echo $pagina; ?></a>
                </li>
            <?php endfor; ?>

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
    Nenhum produto encontrado no estoque.
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>