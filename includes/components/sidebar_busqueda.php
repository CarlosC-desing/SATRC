<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/buscadores.css">

<div class="sidebar" id="sidebar">
    <div class="sidebar__title">
        <h2 class="title__uno">Opciones de</h2>
        <span class="title__dos">Búsqueda</span>
    </div>

    <form class="form--sidebar" method="GET" action="<?= BASE_URL ?>modules/actas/buscar.php">
        <label>Cédula:</label>
        <input class="form__input" type="text" name="cedula" placeholder="Ej: 12345678" value="<?= htmlspecialchars($_GET['cedula'] ?? '') ?>">

        <label>Primer Nombre:</label>
        <input class="form__input" type="text" name="primer_nombre" value="<?= htmlspecialchars($_GET['primer_nombre'] ?? '') ?>">

        <label>Segundo Nombre:</label>
        <input class="form__input" type="text" name="segundo_nombre" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+" title="Solo letras" value="<?= htmlspecialchars($_GET['segundo_nombre'] ?? '') ?>">

        <label>Tercer Nombre:</label>
        <input class="form__input" type="text" name="tercer_nombre" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+" title="Solo letras" value="<?= htmlspecialchars($_GET['tercer_nombre'] ?? '') ?>">

        <label>Primer Apellido:</label>
        <input class="form__input" type="text" name="primer_apellido" value="<?= htmlspecialchars($_GET['primer_apellido'] ?? '') ?>">

        <label>Segundo Apellido:</label>
        <input class="form__input" type="text" name="segundo_apellido" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s']+" title="Solo letras" value="<?= htmlspecialchars($_GET['segundo_apellido'] ?? '') ?>">

        <button class="form__button" type="submit">Buscar</button>
    </form>
</div>