<?php
// plataforma_pedido.php
session_start();
$conexion = new mysqli("localhost", "root", "", "ventas_hamburguesa");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Realizar Pedido</title>
</head>
<body>
    <h2>🛒 Realiza tu pedido</h2>

    <form action="realizar_pedido.php" method="POST">
        <label for="producto">Selecciona un producto:</label>
        <select name="producto" id="producto" required>
            <option value="">-- Elige --</option>
            <?php
            $query = "SELECT * FROM producto";
            $result = $conexion->query($query);

            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['ID_PRODUCTO']}'>{$row['NOMB_PRODUCTO']} - S/ {$row['PRECIO']}</option>";
            }
            ?>
        </select>

        <br><br>
        <label for="cantidad">Cantidad:</label>
        <input type="number" name="cantidad" id="cantidad" min="1" required>

        <br><br>
        <button type="submit">🧾 Confirmar Pedido</button>
    </form>
</body>
</html>