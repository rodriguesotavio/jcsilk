<?php
require_once '../includes/header.php';

if ($_SESSION['cargo'] != 'Administrador') {
    header("location: " . $base_url . "/index.php?status=permission_denied");
    exit();
}

function logs_bind_dynamic(mysqli_stmt $stmt, string $types, array $params): bool
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
$acao_filtro = isset($_GET['acao']) ? trim((string) $_GET['acao']) : '';

$where_clauses = [];
$where_types = '';
$where_params = [];
if ($busca !== '') {
    $where_clauses[] = "(a.detalhes LIKE ? OR u.nome LIKE ?)";
    $where_types .= 'ss';
    $busca_like = '%' . $busca . '%';
    array_push($where_params, $busca_like, $busca_like);
}
if ($acao_filtro !== '') {
    $where_clauses[] = "a.acao LIKE ?";
    $where_types .= 's';
    $where_params[] = '%' . $acao_filtro . '%';
}
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$sql_total = "SELECT COUNT(*) AS total
    FROM acoes a
    JOIN usuarios u ON a.id_usuario = u.id_usuario" . $where_sql;
$stmt_total = mysqli_prepare($link, $sql_total);

if (!$stmt_total) {
    echo '<div class="alert alert-danger">Erro ao carregar total do Log de Auditoria: ' . mysqli_error($link) . '</div>';
    $total_logs = 0;
} else {
    logs_bind_dynamic($stmt_total, $where_types, $where_params);
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);
    $total_logs = (int) mysqli_fetch_assoc($result_total)['total'];
    mysqli_free_result($result_total);
    mysqli_stmt_close($stmt_total);
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
        {$where_sql}
        ORDER BY a.data_acao DESC
        LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($link, $sql);
if ($stmt) {
    $list_types = $where_types . 'ii';
    $list_params = array_merge($where_params, [$itens_por_pagina, $offset]);
    logs_bind_dynamic($stmt, $list_types, $list_params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

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
<form action="" method="get" class="row g-2 align-items-end mb-3">
    <div class="col-12 col-md-5">
        <label for="busca" class="form-label mb-1 small">Busca</label>
        <input type="text" id="busca" name="busca" class="form-control form-control-sm" placeholder="Usuário ou detalhes" value="<?php echo htmlspecialchars($busca); ?>">
    </div>
    <div class="col-12 col-md-4">
        <label for="acao" class="form-label mb-1 small">Ação</label>
        <input type="text" id="acao" name="acao" class="form-control form-control-sm" placeholder="Ex.: Login, Editou Produto" value="<?php echo htmlspecialchars($acao_filtro); ?>">
    </div>
    <div class="col-12 col-md-1 d-grid">
        <input type="hidden" name="itens_por_pagina" value="<?php echo (int) $itens_por_pagina; ?>">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <div class="col-12">
        <a href="index.php" class="btn btn-link btn-sm px-0">Limpar filtros</a>
    </div>
</form>

<?php if ($logs_pagina_atual > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th>ID</th>
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
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        mysqli_stmt_close($stmt);
    }
    ?>

<?php else: ?>
    <div class="alert alert-info">Nenhuma ação crítica registrada no log ainda.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
