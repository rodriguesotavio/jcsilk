<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "/index.php?status=permission_denied");
    exit();
}

$sql = "SELECT
            a.id_log,
            a.acao,
            a.detalhes,
            a.data_acao,
            u.nome AS nome_usuario
        FROM acoes a
        JOIN usuarios u ON a.id_usuario = u.id_usuario
        ORDER BY a.data_acao DESC";

$result = mysqli_query($link, $sql);

if (!$result) {
    echo '<div class="alert alert-danger">Erro ao carregar o Log de Auditoria: ' . mysqli_error($link) . '</div>';
    $total_logs = 0;
} else {
    $total_logs = mysqli_num_rows($result);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Log de Auditoria (<?php echo $total_logs; ?> Registros)</h2>
</div>

<p class="mb-4">Este histórico registra todas as operações críticas realizadas pelos usuários no sistema.</p>

<?php if ($total_logs > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th>ID Log</th>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Detalhes da Operação</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id_log']); ?></td>
                    <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_acao'])); ?></td>
                    <td><?php echo htmlspecialchars($row['nome_usuario']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['acao']); ?></span></td>
                    <td><?php echo nl2br(htmlspecialchars($row['detalhes'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php
    mysqli_free_result($result);
    ?>

<?php else: ?>
    <div class="alert alert-info">Nenhuma ação crítica registrada no log ainda.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>