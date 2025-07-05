<?php
$host = 'localhost';
$db = 'nutriapp';
$user = 'nutri_admin';
$pass = '_Mary190577_';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

echo "<h2>Actualizando estructura de la tabla ejercicios...</h2>";

// Verificar si existe la columna grupo_muscular
$result = $conn->query("SHOW COLUMNS FROM ejercicios LIKE 'grupo_muscular'");
if ($result->num_rows == 0) {
    echo "<p>Agregando columna grupo_muscular...</p>";
    $conn->query("ALTER TABLE ejercicios ADD COLUMN grupo_muscular VARCHAR(100) NOT NULL DEFAULT 'Pecho'");
    echo "<p>✅ Columna grupo_muscular agregada</p>";
} else {
    echo "<p>✅ Columna grupo_muscular ya existe</p>";
}

// Verificar si existe la columna categoria
$result = $conn->query("SHOW COLUMNS FROM ejercicios LIKE 'categoria'");
if ($result->num_rows == 0) {
    echo "<p>Agregando columna categoria...</p>";
    $conn->query("ALTER TABLE ejercicios ADD COLUMN categoria ENUM('Cardio','Fuerza','Flexibilidad','Equilibrio','Funcional') NOT NULL DEFAULT 'Fuerza'");
    echo "<p>✅ Columna categoria agregada</p>";
} else {
    echo "<p>✅ Columna categoria ya existe</p>";
}

// Verificar si existe la columna foto
$result = $conn->query("SHOW COLUMNS FROM ejercicios LIKE 'foto'");
if ($result->num_rows == 0) {
    echo "<p>Agregando columna foto...</p>";
    $conn->query("ALTER TABLE ejercicios ADD COLUMN foto VARCHAR(255) DEFAULT NULL");
    echo "<p>✅ Columna foto agregada</p>";
} else {
    echo "<p>✅ Columna foto ya existe</p>";
}

// Actualizar ejercicios existentes para que tengan grupo_muscular basado en el CSV
echo "<p>Actualizando ejercicios existentes...</p>";
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Pecho' WHERE nombre LIKE '%banca%' OR nombre LIKE '%press%'");
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Piernas' WHERE nombre LIKE '%sentadilla%' OR nombre LIKE '%pierna%'");
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Espalda' WHERE nombre LIKE '%peso muerto%' OR nombre LIKE '%remo%'");
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Hombros' WHERE nombre LIKE '%militar%' OR nombre LIKE '%hombro%'");
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Bíceps' WHERE nombre LIKE '%curl%' OR nombre LIKE '%bíceps%'");
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Tríceps' WHERE nombre LIKE '%tríceps%' OR nombre LIKE '%extensión%'");
$conn->query("UPDATE ejercicios SET grupo_muscular = 'Abdominales' WHERE nombre LIKE '%plancha%' OR nombre LIKE '%abdominal%'");

echo "<p>✅ Ejercicios actualizados</p>";

// Mostrar ejercicios actualizados
echo "<h2>Ejercicios en la base de datos:</h2>";
$result = $conn->query("SELECT id, nombre, grupo_muscular, categoria FROM ejercicios ORDER BY grupo_muscular, nombre");
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nombre</th><th>Grupo Muscular</th><th>Categoría</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
    echo "<td>" . htmlspecialchars($row['grupo_muscular']) . "</td>";
    echo "<td>" . htmlspecialchars($row['categoria']) . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
echo "<p><strong>✅ Actualización completada. Ahora puedes usar la funcionalidad de rutinas.</strong></p>";
?> 