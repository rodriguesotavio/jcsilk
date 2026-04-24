<?php
require_once '../includes/header.php';

$opcoes_itens_por_pagina = [10, 25, 50, 100];
$itens_por_pagina = isset($_GET['itens_por_pagina']) ? (int) $_GET['itens_por_pagina'] : 10;
if (!in_array($itens_por_pagina, $opcoes_itens_por_pagina, true)) {
    $itens_por_pagina = 10;
}
$pagina_atual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$pagina_atual = $pagina_atual > 0 ? $pagina_atual : 1;

$sql_total = "SELECT COUNT(*) AS total FROM estoque";
$result_total = mysqli_query($link, $sql_total);

if (!$result_total) {
    die("Erro ao contar movimentações de estoque: " . mysqli_error($link));
}

$total_registros = (int) mysqli_fetch_assoc($result_total)['total'];
mysqli_free_result($result_total);
$total_paginas = (int) ceil($total_registros / $itens_por_pagina);
$total_paginas = $total_paginas > 0 ? $total_paginas : 1;

if ($pagina_atual > $total_paginas) {
    $pagina_atual = $total_paginas;
}

$offset = ($pagina_atual - 1) * $itens_por_pagina;

$sql = "SELECT
            e.id_movimentacao,
            e.tipo_movimento,
            e.quantidade,
            e.data_movimento,
            e.estornado_de_id,
            p.nome_produto,
            p.preco_unitario,
            u.nome AS nome_usuario_movimento,
            CASE WHEN EXISTS (
                SELECT 1 FROM estoque e2 WHERE e2.estornado_de_id = e.id_movimentacao
            ) THEN 1 ELSE 0 END AS is_correction
        FROM estoque e
        JOIN produtos p ON e.id_produto = p.id_produto
        JOIN usuarios u ON e.id_usuario = u.id_usuario
        ORDER BY e.data_movimento DESC
        LIMIT {$itens_por_pagina} OFFSET {$offset}";

$result = mysqli_query($link, $sql);

if (!$result) {
    die("Erro ao consultar o histórico de estoque: " . mysqli_error($link));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Histórico de Movimentações de Estoque</h2>
    <a href="novo.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nova Movimentação
    </a>
</div>

<?php
if(isset($_GET['status'])){
    $status = $_GET['status'];
    if($status == 'success_move'){
        echo '<div class="alert alert-success">Movimentação registrada e estoque atualizado com sucesso!</div>';
    } else if($status == 'success_revert'){
        echo '<div class="alert alert-warning">Estorno registrado com sucesso! A movimentação original foi revertida e o estoque ajustado.</div>';
    } else if($status == 'move_not_found'){
        echo '<div class="alert alert-danger">Erro: Movimentação original não encontrada para estorno.</div>';
    } else if($status == 'revert_insufficient_stock'){
        echo '<div class="alert alert-danger">Estorno Negado: Não é possível estornar esta movimentação, pois o estoque atual é insuficiente para absorver a baixa reversa.</div>';
    } else if($status == 'date_limit_exceeded'){
        echo '<div class="alert alert-danger">Estorno Negado: O prazo de ' . LIMITE_DIAS_ESTORNO . ' dias para estornar esta movimentação foi excedido. Faça um Ajuste de Estoque.</div>';
    } else if($status == 'cannot_revert_correction'){
        echo '<div class="alert alert-danger">Estorno Negado: Esta movimentação foi um registro de correção (estorno) e não pode ser revertida. Faça um ajuste manual.</div>';
    }
}
?>

<p>Registro de todas as entradas e saídas de produtos no estoque.</p>

<?php
if(mysqli_num_rows($result) > 0):
?>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data/Hora</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th class="text-end">Quantidade</th>
                <!--
                <th class="text-end">Vl. Unitário (Ref.)</th>
                <th class="text-end">Vl. Total (Ref.)</th>
                -->
                <th class="text-center">Usuário</th>
                <th class="text-center">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $limite_segundos = LIMITE_DIAS_ESTORNO * 24 * 60 * 60;
            while($row = mysqli_fetch_assoc($result)):
                $valor_unitario_ref = $row['preco_unitario'];
                $valor_total_ref = $row['quantidade'] * $valor_unitario_ref;

                $data_movimento = strtotime($row['data_movimento']);
                $pode_estornar = (time() - $data_movimento) <= $limite_segundos;
                $pode_estornar = $pode_estornar && is_null($row['estornado_de_id']) && $row['is_correction'] == 0;
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id_movimentacao']); ?></td>
                <td>
                    <?php echo date('d/m/Y H:i:s', strtotime($row['data_movimento'])); ?>
                </td>
                <td><?php echo htmlspecialchars($row['nome_produto']); ?></td>
                <td>
                    <span class="badge
                        <?php echo ($row['tipo_movimento'] == 'Entrada') ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo htmlspecialchars($row['tipo_movimento']); ?>
                    </span>
                </td>
                <td class="text-end"><?php echo htmlspecialchars($row['quantidade']); ?></td>
                <!--
                <td class="text-end">R$ <?php echo number_format($valor_unitario_ref, 2, ',', '.'); ?></td>
                <td class="text-end"><b>R$ <?php echo number_format($valor_total_ref, 2, ',', '.'); ?></b></td>
                -->
                <td class="text-center"><?php echo htmlspecialchars($row['nome_usuario_movimento']); ?></td>
                <td class="text-center">
                <?php if ($pode_estornar): ?>
                    <a href="estornar.php?id=<?php echo $row['id_movimentacao']; ?>" class="btn btn-sm btn-warning" title="Estornar Movimentação"
                       onclick="return confirm('ATENÇÃO! Tem certeza que deseja estornar esta movimentação? Isso criará um registro reverso e ajustará o estoque.');">
                        <i class="fas fa-undo"></i> Estornar
                    </a>
                <?php elseif (!is_null($row['estornado_de_id'])): ?>
                    <span class="badge bg-danger" title="Movimentação estornada pelo registro #<?php echo $row['estornado_de_id']; ?>">Estornado</span>
                <?php elseif ($row['is_correction'] == 1): ?>
                    <span class="badge bg-info text-dark" title="Movimentação de ajuste/correção.">Correção</span>
                <?php else: ?>
                    <span class="text-muted small" title="Prazo de estorno excedido (<?php echo LIMITE_DIAS_ESTORNO; ?> dias).">---</span>
                <?php endif; ?>

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
<nav aria-label="Paginação de movimentações de estoque" class="mt-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
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
    </div>
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
    Nenhuma movimentação de estoque encontrada.
</div>
<?php endif; ?>

<?php
require_once '../includes/footer.php';
?>