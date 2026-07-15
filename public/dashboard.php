
<?php
/**
 * Dashboard de Auditoría de Usuarios - Optimización de Consumo de Memoria
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control de Acceso Perimetral (Capa de Autenticación)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/conexion.php';

// 1. Sanitización de Parámetros de Control (Filtros de Entrada)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 10; // Restricción estricta de registros por lote (Paginación)
$offset = ($page - 1) * $limit;

try {
    // 2. Construcción de Consulta DQL Optimizada
    // Se evitan consultas comodín (SELECT *) especificando solo campos requeridos
    $queryStr = "SELECT id, username, email, created_at 
                 FROM usuarios 
                 WHERE 1=1";

    $params = [];

    // Búsqueda indexada selectiva (WHERE)
    if (!empty($search)) {
        $queryStr .= " AND (username LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    // Ordenamiento controlado (ORDER BY) y Limitación física (LIMIT/OFFSET)
    $queryStr .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($queryStr);

    // Enlazar parámetros de paginación de forma explícita por tipo de dato
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }

    $stmt->execute();
    $resultados = $stmt->fetchAll();

} catch (\PDOException $e) {
    error_log("Fallo en consulta de auditoría: " . $e->getMessage());
    $resultados = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Auditoría</title>
</head>
<body>
    <h2>Panel de Auditoría de Datos - Sesión: <?= htmlspecialchars($_SESSION['username']) ?></h2>
    
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Buscar por usuario o email..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Filtrar</button>
    </form>
    
    <br>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Email</th>
                <th>Fecha Creación</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resultados)): ?>
                <?php foreach ($resultados as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No se encontraron registros.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
