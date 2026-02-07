<form method="GET" action="<?= BASE_URL ?>modules/actas/buscar.php">
    <input type="hidden" name="modo" value="actas">
    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">

    <label>Número de Acta:</label>
    <input type="text" name="numero_acta" placeholder="Ej: 155">

    <label>Tipo de Acta:</label>
    <select name="tipo_acta">
        <option value="">Todos</option>
        <option value="nacimiento">Nacimiento</option>
        <option value="matrimonio">Matrimonio</option>
        <option value="defuncion">Defunción</option>
        <option value="union">Union estable de hecho</option>
    </select>

    <button type="submit">🔍 Buscar Actas</button>
</form>