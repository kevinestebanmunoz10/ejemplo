<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("Connection/Conect.php");
require_once("function/function.php");

$db = new Database();
$pdo = $db->conectar();

if ($pdo === null) {
    die('Error de conexion a la base de datos');
}

$dietas  = obtenerTodasLasDietas($pdo);
$accion  = $_GET['accion'] ?? 'menu';
$mensaje = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST['crear'])) {
        $id_animal = intval($_POST['id_animal']);
        $especie   = trim($_POST['especie']);
        $animal    = trim($_POST['animal']);
        $habitat   = trim($_POST['habitat']);
        $color     = trim($_POST['color']);
        $sexo      = trim($_POST['sexo']);
        $dieta_id  = isset($_POST['dieta_id']) && $_POST['dieta_id'] !== '' ? intval($_POST['dieta_id']) : null;
        $imagen    = null;
        $alert_img = '';

        $rutaDestino = __DIR__ . '/img/';
        if (!is_dir($rutaDestino)) {
            mkdir($rutaDestino, 0777, true);
        }

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $max = 3 * 1024 * 1024;
            if ($ext !== 'png') {
                $alert_img = "Solo se permiten archivos .png";
            } elseif ($_FILES['imagen']['size'] > $max) {
                $alert_img = "El archivo no puede superar los 3mb";
            } else {
                $nombre_img = uniqid('img_') . '.png';
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino . $nombre_img)) {
                    $imagen = $nombre_img;
                } else {
                    $alert_img = "Error al guardar imagen en la carpeta /img";
                }
            }
        }

        if ($id_animal > 0 && $especie !== '' && $alert_img === '') {
            $ok = insertarAnimal($pdo, $id_animal, $especie, $animal, $habitat, $color, $sexo, $dieta_id, $imagen);
            $mensaje = $ok ? "Animal creado correctamente. ID: $id_animal" : "Error al insertar (¿ID duplicado?)";
        } else {
            $mensaje = $alert_img ?: "El ID y la especie son obligatorios";
        }

    } elseif (isset($_POST['actualizar'])) {
        $id       = intval($_POST['id']);
        $especie  = trim($_POST['especie']);
        $animal   = trim($_POST['animal']);
        $habitat  = trim($_POST['habitat']);
        $color    = trim($_POST['color']);
        $sexo     = trim($_POST['sexo']);
        $dieta_id = isset($_POST['dieta_id']) && $_POST['dieta_id'] !== '' ? intval($_POST['dieta_id']) : null;
        $imagen   = null;
        $alert_img = '';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $max = 3 * 1024 * 1024;
            if ($ext !== 'png') {
                $alert_img = "Solo se permiten archivos .png";
            } elseif ($_FILES['imagen']['size'] > $max) {
                $alert_img = "El archivo no puede superar los 3mb";
            } else {
                $nombre_img = uniqid('img_') . '.png';
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], __DIR__ . '/img/' . $nombre_img)) {
                    $old = obtenerAnimalPorId($pdo, $id);
                    if (!empty($old['img']) && file_exists(__DIR__ . '/img/' . $old['img'])) {
                        unlink(__DIR__ . '/img/' . $old['img']);
                    }
                    $imagen = $nombre_img;
                } else {
                    $alert_img = "Error al guardar la imagen";
                }
            }
        } elseif (isset($_POST['borrar_imagen'])) {
            $imagen = '';
        }

        if ($id > 0 && $especie !== '' && $alert_img === '') {
            $ok = actualizarAnimal($pdo, $id, $especie, $animal, $habitat, $color, $sexo, $dieta_id, $imagen);
            $mensaje = $ok ? "Animal actualizado correctamente" : "No se encontro el ID del animal";
        } else {
            $mensaje = $alert_img ?: "Datos invalidos para actualizar";
        }

    } elseif (isset($_POST['eliminar'])) {
        $id = intval($_POST['id']);
        $ok = eliminarAnimal($pdo, $id);
        $mensaje = $ok ? "Animal eliminado correctamente" : "No se encontro el animal";

    } elseif (isset($_POST['crear_dieta'])) {
        $nombre_dieta = trim($_POST['nombre_dieta']);
        if ($nombre_dieta !== '') {
            $ok = crearDieta($pdo, $nombre_dieta);
            $mensaje = $ok ? "Dieta creada correctamente" : "Error al crear dieta";
        } else {
            $mensaje = "El nombre de la dieta es obligatorio";
        }

    } elseif (isset($_POST['actualizar_dieta'])) {
        $id_dieta     = intval($_POST['id_dieta']);
        $nombre_dieta = trim($_POST['nombre_dieta']);
        if ($id_dieta > 0 && $nombre_dieta !== '') {
            $ok = actualizarDieta($pdo, $id_dieta, $nombre_dieta);
            $mensaje = $ok ? "Dieta actualizada correctamente" : "Error al actualizar dieta";
        } else {
            $mensaje = "Datos invalidos";
        }

    } elseif (isset($_POST['eliminar_dieta'])) {
        $id_dieta = intval($_POST['id_dieta']);
        $ok = eliminarDieta($pdo, $id_dieta);
        $mensaje = $ok ? "Dieta eliminada correctamente" : "No se encontro la dieta";
    }
}

if ($accion === "listar") {
    $animales = obtenerTodosLosAnimales($pdo);
}

$animal_editar = null;
if ($accion === 'editar_form') {
    $id_buscar = intval($_GET['id'] ?? 0);
    if ($id_buscar > 0) {
        $animal_editar = obtenerAnimalPorId($pdo, $id_buscar);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Animales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4 text-center">CRUD de Animales <i class="fa-solid fa-paw"></i></h1>

    <?php if ($mensaje): ?>
        <div class="alert alert-info shadow-sm">
            <strong><?= htmlspecialchars($mensaje) ?></strong>
            <p class="mb-0 mt-2"><a href="?accion=menu" class="btn btn-sm btn-primary">Volver al menu</a></p>
        </div>

    <?php else: ?>

        <?php if ($accion === 'menu'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Seleccione una opcion:</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><a href="?accion=listar" class="text-decoration-none text-primary">Listar todos los animales</a></li>
                        <li class="list-group-item"><a href="?accion=crear_form" class="text-decoration-none text-primary">Crear nuevo animal</a></li>
                        <li class="list-group-item"><a href="?accion=editar_form" class="text-decoration-none text-primary">Actualizar animal</a></li>
                        <li class="list-group-item"><a href="?accion=eliminar_form" class="text-decoration-none text-primary">Eliminar animal</a></li>
                        <li class="list-group-item"><a href="?accion=dietas" class="text-decoration-none text-warning">Gestionar dietas</a></li>
                    </ul>
                </div>
            </div>

        <?php elseif ($accion === 'listar'): ?>
            <h2 class="mb-3">Listado de Animales <i class="fa-solid fa-book"></i></h2>
            <?php if (count($animales ?? []) > 0): ?>
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Especie</th>
                            <th>Animal</th>
                            <th>Hábitat</th>
                            <th>Color</th>
                            <th>Sexo</th>
                            <th>Dieta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($animales ?? [] as $a): ?>
                            <tr>
                                <td><?= htmlspecialchars($a['id_animal']) ?></td>
                                <td>
                                    <?php if (!empty($a['img'])): ?>
                                        <img src="img/<?= htmlspecialchars($a['img']) ?>"
                                            style="max-height:50px; border-radius:4px;" alt="Animal">
                                    <?php else: ?>
                                        <span class="text-muted">Sin foto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($a['especie']) ?></td>
                                <td><?= htmlspecialchars($a['animal']) ?></td>
                                <td><?= htmlspecialchars($a['habitat']) ?></td>
                                <td><?= htmlspecialchars($a['color']) ?></td>
                                <td><?= htmlspecialchars($a['sexo']) ?></td>
                                <td><?= htmlspecialchars($a['dieta_nombre'] ?? 'Sin dieta') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No hay animales registrados.</div>
            <?php endif; ?>
            <a href="?accion=menu" class="btn btn-secondary mt-3">Volver al menu</a>

        <?php elseif ($accion === 'crear_form'): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Nuevo Animal <i class="fa-solid fa-circle-plus"></i></h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">ID:</label>
                            <input type="number" name="id_animal" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especie:</label>
                            <input type="text" name="especie" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Animal:</label>
                            <input type="text" name="animal" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hábitat:</label>
                            <input type="text" name="habitat" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color:</label>
                            <input type="text" name="color" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sexo:</label>
                            <select name="sexo" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                <option value="Macho">Macho</option>
                                <option value="Hembra">Hembra</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dieta:</label>
                            <select name="dieta_id" class="form-select">
                                <option value="">-- Sin dieta --</option>
                                <?php foreach ($dietas as $d): ?>
                                    <option value="<?= $d['type_id'] ?>"><?= htmlspecialchars($d['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen (.png, max 3mb)</label>
                            <input type="file" name="imagen" class="form-control" accept=".png">
                        </div>
                        <button type="submit" name="crear" class="btn btn-primary">Guardar</button>
                        <a href="?accion=menu" class="btn btn-outline-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'editar_form' && !$animal_editar): ?>
            <h2>Actualizar Animal <i class="fa-solid fa-pen"></i></h2>
            <p>Ingrese el ID del animal a editar</p>
            <form action="" method="GET">
                <input type="hidden" name="accion" value="editar_form">
                <div class="input-group mb-3" style="max-width:300px;">
                    <span class="input-group-text">ID</span>
                    <input type="number" name="id" required min="1" class="form-control">
                    <button type="submit" class="btn btn-success">Buscar</button>
                </div>
            </form>
            <a href="?accion=menu" class="btn btn-secondary">Volver al menu</a>

        <?php elseif ($animal_editar): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-3">Editar Animal #<?= $animal_editar['id_animal'] ?></h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $animal_editar['id_animal'] ?>">
                        <div class="mb-3">
                            <label class="form-label">ID:</label>
                            <input type="number" class="form-control" value="<?= $animal_editar['id_animal'] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especie:</label>
                            <input type="text" class="form-control" name="especie" value="<?= htmlspecialchars($animal_editar['especie']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Animal:</label>
                            <input type="text" class="form-control" name="animal" value="<?= htmlspecialchars($animal_editar['animal']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hábitat:</label>
                            <input type="text" class="form-control" name="habitat" value="<?= htmlspecialchars($animal_editar['habitat']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Color:</label>
                            <input type="text" class="form-control" name="color" value="<?= htmlspecialchars($animal_editar['color']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sexo:</label>
                            <select name="sexo" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                <option value="Macho"  <?= $animal_editar['sexo'] === 'Macho'  ? 'selected' : '' ?>>Macho</option>
                                <option value="Hembra" <?= $animal_editar['sexo'] === 'Hembra' ? 'selected' : '' ?>>Hembra</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dieta:</label>
                            <select name="dieta_id" class="form-select">
                                <option value="">-- Sin dieta --</option>
                                <?php foreach ($dietas as $d): ?>
                                    <option value="<?= $d['type_id'] ?>" <?= $animal_editar['dieta_id'] == $d['type_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagen actual</label>
                            <?php if (!empty($animal_editar['img'])): ?>
                                <img src="img/<?= htmlspecialchars($animal_editar['img']) ?>"
                                    style="max-height:180px; border-radius:4px;" class="d-block mb-2" alt="Animal">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="borrar_imagen" id="delImg">
                                    <label class="form-check-label" for="delImg">Eliminar imagen actual</label>
                                </div>
                            <?php else: ?>
                                <span class="text-muted d-block">Sin imagen</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva imagen (.png, max 3mb)</label>
                            <input type="file" name="imagen" class="form-control" accept=".png">
                        </div>
                        <button type="submit" name="actualizar" class="btn btn-warning">Actualizar</button>
                        <a href="?accion=menu" class="btn btn-outline-secondary">Cancelar</a>
                    </form>
                </div>
            </div>

        <?php elseif ($accion === 'eliminar_form'): ?>
            <h2>Eliminar Animal <i class="fa-solid fa-trash-can"></i></h2>
            <p>Ingrese el ID del animal a eliminar</p>
            <form action="" method="POST">
                <div class="input-group mb-3" style="max-width:300px;">
                    <span class="input-group-text">ID</span>
                    <input type="number" name="id" required min="1" class="form-control">
                    <button type="submit" name="eliminar"
                        onclick="return confirm('¿Estas seguro de eliminar este animal?')"
                        class="btn btn-danger">Eliminar</button>
                </div>
            </form>
            <a href="?accion=menu" class="btn btn-outline-secondary">Volver al menu</a>

        <?php elseif ($accion === 'dietas'): ?>
            <?php $lista_dietas = obtenerTodasLasDietas($pdo); ?>
            <h2> Tipos de Dieta</h2>

            <div class="card mb-3 bg-light">
                <div class="card-body">
                    <form method="POST" class="d-flex gap-2 align-items-center">
                        <input type="text" name="nombre_dieta" class="form-control"
                            placeholder="Nueva dieta (ej: Herbivoro)" required>
                        <button type="submit" name="crear_dieta" class="btn btn-success">➕ Agregar</button>
                    </form>
                </div>
            </div>

            <?php if (count($lista_dietas) > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_dietas as $d): ?>
                            <tr>
                                <td><?= $d['type_id'] ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="id_dieta" value="<?= $d['type_id'] ?>">
                                        <input type="text" name="nombre_dieta"
                                            value="<?= htmlspecialchars($d['type_name']) ?>"
                                            class="form-control form-control-sm" required>
                                        <button type="submit" name="actualizar_dieta" class="btn btn-sm btn-primary">💾</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Eliminar esta dieta? Los animales perderan su dieta asignada.');">
                                        <input type="hidden" name="id_dieta" value="<?= $d['type_id'] ?>">
                                        <button type="submit" name="eliminar_dieta" class="btn btn-sm btn-danger">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay dietas registradas.</p>
            <?php endif; ?>
            <a href="?accion=menu" class="btn btn-secondary mt-3">Volver al menu</a>

        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>