<?php

function obtenerTodosLosAnimales(PDO $pdo): array {
    $sql = "SELECT a.id_animal, a.especie, a.animal, a.habitat, a.color, a.sexo, a.img,
                   d.type_name AS dieta_nombre
            FROM animal a
            LEFT JOIN dieta d ON a.dieta_id = d.type_id
            ORDER BY a.id_animal ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerAnimalPorId(PDO $pdo, int $id) {
    $sql = "SELECT id_animal, especie, animal, habitat, color, sexo, img, dieta_id
            FROM animal WHERE id_animal = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertarAnimal(PDO $pdo, int $id_animal, string $especie, string $animal, string $habitat, string $color, string $sexo, ?int $dieta_id, ?string $imagen): bool {
    $stmt = $pdo->prepare('INSERT INTO animal (id_animal, especie, animal, habitat, color, sexo, dieta_id, img) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    try {
        $stmt->execute([$id_animal, $especie, $animal, $habitat, $color, $sexo, $dieta_id, $imagen]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al insertar animal: ' . $e->getMessage());
        return false;
    }
}

function actualizarAnimal(PDO $pdo, int $id, string $especie, string $animal, string $habitat, string $color, string $sexo, ?int $dieta_id, ?string $imagen): bool {
    $sql = $imagen === ''
        ? 'UPDATE animal SET especie=?, animal=?, habitat=?, color=?, sexo=?, dieta_id=?, img=NULL WHERE id_animal=?'
        : 'UPDATE animal SET especie=?, animal=?, habitat=?, color=?, sexo=?, dieta_id=?, img=COALESCE(?,img) WHERE id_animal=?';
    $stmt = $pdo->prepare($sql);
    try {
        if ($imagen === '') {
            $stmt->execute([$especie, $animal, $habitat, $color, $sexo, $dieta_id, $id]);
        } else {
            $stmt->execute([$especie, $animal, $habitat, $color, $sexo, $dieta_id, $imagen, $id]);
        }
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al actualizar animal: ' . $e->getMessage());
        return false;
    }
}

function eliminarAnimal(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare('DELETE FROM animal WHERE id_animal=?');
    try {
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al eliminar animal: ' . $e->getMessage());
        return false;
    }
}

// ── Dieta (equivalente a tipos) ──────────────────────────────────────────────

function obtenerTodasLasDietas(PDO $pdo): array {
    $stmt = $pdo->prepare("SELECT * FROM dieta ORDER BY type_name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearDieta(PDO $pdo, string $nombre): int|false {
    $sql = "INSERT INTO dieta (type_name) VALUES (?) ON DUPLICATE KEY UPDATE type_name = VALUES(type_name)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$nombre]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error al crear dieta: ' . $e->getMessage());
        return false;
    }
}

function actualizarDieta(PDO $pdo, int $id, string $nombre): bool {
    $stmt = $pdo->prepare("UPDATE dieta SET type_name = ? WHERE type_id = ?");
    try {
        $stmt->execute([$nombre, $id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al actualizar dieta: ' . $e->getMessage());
        return false;
    }
}

function eliminarDieta(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("DELETE FROM dieta WHERE type_id = ?");
    try {
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('Error al eliminar dieta: ' . $e->getMessage());
        return false;
    }
}