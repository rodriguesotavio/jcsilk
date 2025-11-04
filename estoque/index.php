<?php
require_once '../includes/header.php';

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
        ORDER BY e.data_movimento DESC";

$result = mysqli_query($link, $sql);

if (!$result) {
    die("Erro ao consultar o histórico de estoque: " . mysqli_error($link));
}
?>

<style>
.col-qtd {
    width: 150px;
}
</style>


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