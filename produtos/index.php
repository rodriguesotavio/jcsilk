<?php
require_once '../includes/header.php';

$sql = "SELECT p.id_produto, p.nome_produto, p.categoria, p.preco_unitario, p.quantidade_estoque, f.nome AS nome_fornecedor
        FROM produtos p
        JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
        ORDER BY p.id_produto DESC";

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
mysqli_free_result($result);
else:
?>
<div class="alert alert-info" role="alert">
    Nenhum produto encontrado no estoque.
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>