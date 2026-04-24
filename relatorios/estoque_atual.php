<?php
require_once '../includes/header.php';

$categoria = isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : '';

$sql = "SELECT p.nome_produto, p.categoria, p.quantidade_estoque, p.estoque_minimo, f.nome AS fornecedor
        FROM produtos p
        JOIN fornecedores f ON p.fornecedor_id = f.id_fornecedor
        WHERE (? = '' OR p.categoria LIKE ?)
        ORDER BY p.quantidade_estoque ASC, p.nome_produto ASC";
$stmt = mysqli_prepare($link, $sql);
$categoriaLike = '%' . $categoria . '%';
mysqli_stmt_bind_param($stmt, "ss", $categoria, $categoriaLike);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
mysqli_free_result($result);
mysqli_stmt_close($stmt);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Relatório: Estoque Atual</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Voltar</a>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-4">
        <label for="categoria" class="form-label mb-1 small">Categoria</label>
        <input type="text" id="categoria" name="categoria" class="form-control form-control-sm" value="<?php echo htmlspecialchars($categoria); ?>">
    </div>
    <div class="col-12 col-md-1 d-grid">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-12 col-md-2 d-grid">
        <button type="button" class="btn btn-outline-primary" id="exportPdfBtn">Exportar PDF</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle" id="reportTable">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Categoria</th>
                <th class="text-end">Estoque Atual</th>
                <th class="text-end">Estoque Mínimo</th>
                <th>Status</th>
                <th>Fornecedor</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $qtd = (int) $row['quantidade_estoque'];
                        $minimo = (int) $row['estoque_minimo'];
                        $status = $qtd === 0 ? 'Em Falta' : ($qtd <= $minimo ? 'Crítico' : 'Normal');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nome_produto']); ?></td>
                        <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                        <td class="text-end"><?php echo $qtd; ?></td>
                        <td class="text-end"><?php echo $minimo; ?></td>
                        <td><?php echo $status; ?></td>
                        <td><?php echo htmlspecialchars($row['fornecedor']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
document.getElementById('exportPdfBtn').addEventListener('click', function () {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');
    doc.setFontSize(12);
    doc.text('Relatorio de Estoque Atual', 14, 12);
    doc.autoTable({ html: '#reportTable', startY: 16, styles: { fontSize: 8 } });
    doc.save('relatorio-estoque-atual.pdf');
});
</script>

<?php require_once '../includes/footer.php'; ?>
