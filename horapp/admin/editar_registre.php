<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/header.php';

requireRole('admin');

$pdo = getDB();
$error = '';
$msg = '';

// Obtenir tots els registres amb filtres
$filtre_usuari = filter_var($_GET['usuari_id'] ?? 0, FILTER_VALIDATE_INT);
$filtre_data   = htmlspecialchars($_GET['data'] ?? date('Y-m-d'));

// Obtenir llista d'empleats per al filtre
$stmt = $pdo->prepare('SELECT id, nom, cognoms FROM usuaris WHERE rol = :rol AND actiu = 1 ORDER BY cognoms, nom');
$stmt->execute([':rol' => 'empleat']);
$empleats = $stmt->fetchAll();

// Obtenir registres segons filtre
$query = '
    SELECT r.id, r.data, r.hora_entrada, r.hora_sortida, r.total_hores, r.notes,
           u.nom, u.cognoms, p.nom AS projecte, r.usuari_id, r.projecte_id
    FROM registres_hores r
    JOIN usuaris u ON r.usuari_id = u.id
    JOIN projectes p ON r.projecte_id = p.id
    WHERE 1=1
';
$params = [];

if ($filtre_usuari) {
    $query .= ' AND r.usuari_id = :usuari_id';
    $params[':usuari_id'] = $filtre_usuari;
}
if ($filtre_data) {
    $query .= ' AND r.data = :data';
    $params[':data'] = $filtre_data;
}
$query .= ' ORDER BY r.hora_entrada DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$registres = $stmt->fetchAll();

// Obtenir projectes per al formulari d'edició
$stmt = $pdo->prepare('SELECT id, nom FROM projectes WHERE actiu = 1 ORDER BY nom');
$stmt->execute();
$projectes = $stmt->fetchAll();

// Processar edició
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accio']) && $_POST['accio'] === 'editar') {
    $id           = filter_var($_POST['registre_id'] ?? 0, FILTER_VALIDATE_INT);
    $hora_entrada = htmlspecialchars($_POST['hora_entrada'] ?? '');
    $hora_sortida = htmlspecialchars($_POST['hora_sortida'] ?? '');
    $projecte_id  = filter_var($_POST['projecte_id'] ?? 0, FILTER_VALIDATE_INT);
    $notes        = htmlspecialchars($_POST['notes'] ?? '');
    $data         = htmlspecialchars($_POST['data'] ?? '');

    if (!$id || empty($hora_entrada) || !$projecte_id || empty($data)) {
        $error = 'Tots els camps obligatoris han d\'estar omplerts.';
    } else {
        try {
            if (!empty($hora_sortida)) {
                $entrada_dt  = new DateTime($data . ' ' . $hora_entrada);
                $sortida_dt  = new DateTime($data . ' ' . $hora_sortida);
                $diff        = $entrada_dt->diff($sortida_dt);
                $total_hores = round($diff->h + ($diff->i / 60), 2);

                $stmt = $pdo->prepare('
                    UPDATE registres_hores 
                    SET hora_entrada = :entrada, hora_sortida = :sortida, 
                        total_hores = :total, projecte_id = :pid, notes = :notes, data = :data
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':entrada' => $data . ' ' . $hora_entrada,
                    ':sortida' => $data . ' ' . $hora_sortida,
                    ':total'   => $total_hores,
                    ':pid'     => $projecte_id,
                    ':notes'   => $notes,
                    ':data'    => $data,
                    ':id'      => $id
                ]);
            } else {
                $stmt = $pdo->prepare('
                    UPDATE registres_hores 
                    SET hora_entrada = :entrada, hora_sortida = NULL,
                        total_hores = NULL, projecte_id = :pid, notes = :notes, data = :data
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':entrada' => $data . ' ' . $hora_entrada,
                    ':pid'     => $projecte_id,
                    ':notes'   => $notes,
                    ':data'    => $data,
                    ':id'      => $id
                ]);
            }
            $msg = 'Registre actualitzat correctament.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Error en actualitzar el registre.';
        }
    }
}

// Processar eliminació
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accio']) && $_POST['accio'] === 'eliminar') {
    $id = filter_var($_POST['registre_id'] ?? 0, FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM registres_hores WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $msg = 'Registre eliminat correctament.';
    }
}
?>

<h2 class="mb-4">✏️ Editar Registres d'Hores</h2>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<!-- Filtre -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Empleat</label>
                <select name="usuari_id" class="form-select">
                    <option value="">-- Tots els empleats --</option>
                    <?php foreach ($empleats as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $filtre_usuari == $e['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['nom'] . ' ' . $e['cognoms']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Data</label>
                <input type="date" name="data" class="form-control" value="<?= $filtre_data ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Taula registres -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empleat</th>
                    <th>Data</th>
                    <th>Projecte</th>
                    <th>Entrada</th>
                    <th>Sortida</th>
                    <th>Total</th>
                    <th>Notes</th>
                    <th>Accions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registres)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">Sense registres</td></tr>
                <?php else: ?>
                    <?php foreach ($registres as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nom'] . ' ' . $r['cognoms']) ?></td>
                            <td><?= htmlspecialchars($r['data']) ?></td>
                            <td><?= htmlspecialchars($r['projecte']) ?></td>
                            <td><?= htmlspecialchars(date('H:i', strtotime($r['hora_entrada']))) ?></td>
                            <td><?= $r['hora_sortida'] ? htmlspecialchars(date('H:i', strtotime($r['hora_sortida']))) : '<span class="badge bg-success">Actiu</span>' ?></td>
                            <td><?= $r['total_hores'] ? $r['total_hores'] . 'h' : '-' ?></td>
                            <td><?= htmlspecialchars($r['notes'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                    onclick="obrirEdicio(<?= htmlspecialchars(json_encode($r)) ?>)">
                                    Editar
                                </button>
                                <form method="POST" action="" style="display:inline"
                                    onsubmit="return confirm('Eliminar aquest registre?')">
                                    <input type="hidden" name="accio" value="eliminar">
                                    <input type="hidden" name="registre_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal edició -->
<div class="modal fade" id="modalEdicio" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✏️ Editar Registre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="accio" value="editar">
                    <input type="hidden" name="registre_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" name="data" id="edit_data" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Projecte</label>
                        <select name="projecte_id" id="edit_projecte" class="form-select" required>
                            <?php foreach ($projectes as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hora entrada</label>
                            <input type="time" name="hora_entrada" id="edit_entrada" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hora sortida</label>
                            <input type="time" name="hora_sortida" id="edit_sortida" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" id="edit_notes" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel·lar</button>
                    <button type="submit" class="btn btn-primary">Guardar canvis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function obrirEdicio(r) {
    document.getElementById('edit_id').value      = r.id;
    document.getElementById('edit_data').value    = r.data;
    document.getElementById('edit_projecte').value = r.projecte_id;
    document.getElementById('edit_entrada').value = r.hora_entrada ? r.hora_entrada.substring(11, 16) : '';
    document.getElementById('edit_sortida').value = r.hora_sortida ? r.hora_sortida.substring(11, 16) : '';
    document.getElementById('edit_notes').value   = r.notes ?? '';
    new bootstrap.Modal(document.getElementById('modalEdicio')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>