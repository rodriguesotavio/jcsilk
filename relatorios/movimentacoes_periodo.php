<?php
require_once '../includes/header.php';

$data_inicio = isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '' ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) && $_GET['data_fim'] !== '' ? $_GET['data_fim'] : date('Y-m-d');

$sql = "SELECT
            p.nome_produto,
            SUM(CASE WHEN e.tipo_movimento = 'Entrada' THEN e.quantidade ELSE 0 END) AS total_entradas,
            SUM(CASE WHEN e.tipo_movimento = 'Saída' THEN e.quantidade ELSE 0 END) AS total_saidas,
            COUNT(*) AS total_movimentacoes
        FROM estoque e
        JOIN produtos p ON e.id_produto = p.id_produto
        WHERE DATE(e.data_movimento) BETWEEN ? AND ?
        GROUP BY p.id_produto, p.nome_produto
        ORDER BY total_movimentacoes DESC, p.nome_produto ASC";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "ss", $data_inicio, $data_fim);
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
    <h2>Relatório: Movimentações por Período</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">Voltar</a>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-3">
        <label for="data_inicio" class="form-label mb-1 small">Data inicial</label>
        <input type="date" id="data_inicio" name="data_inicio" class="form-control form-control-sm" value="<?php echo htmlspecialchars($data_inicio); ?>">
    </div>
    <div class="col-12 col-md-3">
        <label for="data_fim" class="form-label mb-1 small">Data final</label>
        <input type="date" id="data_fim" name="data_fim" class="form-control form-control-sm" value="<?php echo htmlspecialchars($data_fim); ?>">
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
                <th class="text-end">Entradas</th>
                <th class="text-end">Saídas</th>
                <th class="text-end">Total Movimentações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($rows) === 0): ?>
                <tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado para o período.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nome_produto']); ?></td>
                        <td class="text-end"><?php echo (int) $row['total_entradas']; ?></td>
                        <td class="text-end"><?php echo (int) $row['total_saidas']; ?></td>
                        <td class="text-end"><?php echo (int) $row['total_movimentacoes']; ?></td>
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
    const doc = new jsPDF('p', 'mm', 'a4');
    doc.setFontSize(12);
    doc.text('Relatorio de Movimentacoes por Periodo', 14, 12);
    doc.autoTable({ html: '#reportTable', startY: 16, styles: { fontSize: 8 } });
    doc.save('relatorio-movimentacoes-periodo.pdf');
});
</script>

<?php require_once '../includes/footer.php'; ?>
