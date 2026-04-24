<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "/index.php?status=permission_denied");
    exit();
}

$opcoes_itens_por_pagina = [10, 25, 50, 100];
$itens_por_pagina = isset($_GET['itens_por_pagina']) ? (int) $_GET['itens_por_pagina'] : 10;
if (!in_array($itens_por_pagina, $opcoes_itens_por_pagina, true)) {
    $itens_por_pagina = 10;
}
$pagina_atual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$pagina_atual = $pagina_atual > 0 ? $pagina_atual : 1;

$sql_total = "SELECT COUNT(*) AS total FROM acoes";
$result_total = mysqli_query($link, $sql_total);

if (!$result_total) {
    echo '<div class="alert alert-danger">Erro ao carregar total do Log de Auditoria: ' . mysqli_error($link) . '</div>';
    $total_logs = 0;
} else {
    $total_logs = (int) mysqli_fetch_assoc($result_total)['total'];
    mysqli_free_result($result_total);
}

$total_paginas = (int) ceil($total_logs / $itens_por_pagina);
$total_paginas = $total_paginas > 0 ? $total_paginas : 1;

if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql = "SELECT
            a.id_log,
            a.acao,
            a.detalhes,
            a.data_acao,
            u.nome AS nome_usuario
        FROM acoes a
        JOIN usuarios u ON a.id_usuario = u.id_usuario
        ORDER BY a.data_acao DESC
        LIMIT {$itens_por_pagina} OFFSET {$offset}";

$result = mysqli_query($link, $sql);

if (!$result) {
    echo '<div class="alert alert-danger">Erro ao carregar o Log de Auditoria: ' . mysqli_error($link) . '</div>';
    $logs_pagina_atual = 0;
} else {
    $logs_pagina_atual = mysqli_num_rows($result);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Log de Auditoria (<?php echo $total_logs; ?> Registros)</h2>
</div>

<p class="mb-4">Este histórico registra todas as operações críticas realizadas pelos usuários no sistema.</p>

<?php if ($logs_pagina_atual > 0): ?>
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
    $query_params = $_GET;
    unset($query_params['pagina']);
    ?>
    <nav aria-label="Paginação de logs de auditoria" class="mt-4">
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
    ?>

<?php else: ?>
    <div class="alert alert-info">Nenhuma ação crítica registrada no log ainda.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>