<?php
require_once '../includes/header.php';

$sql = "SELECT id_fornecedor, nome, telefone, email, UF, cep FROM fornecedores";
$result = mysqli_query($link, $sql);

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
mysqli_close($link);

else:
?>
<div class="alert alert-info" role="alert">
    Nenhum fornecedor encontrado no banco de dados.
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>