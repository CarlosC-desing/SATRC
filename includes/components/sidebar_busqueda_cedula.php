<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/buscadores.css">

<div id="sidebar">
    <div class="sidebar__title">
        <h2 class="title__uno">Buscar por</h2>
        <span class="title__dos">Cédula</span>
    </div>
    <form class="form" id="buscar-cedula-form">
        <input class="form__input form__input--cedula" type="text" id="cedula-input" placeholder="Cédula" required>
        <button class="form__button" type="submit">Buscar</button>
    </form>
    <div id="resultado-cedula"></div>
</div>

<script>
    function copiarTexto(event, idElemento) {
        const elemento = document.getElementById(idElemento);
        if (!elemento) return;

        const texto = elemento.innerText;
        navigator.clipboard.writeText(texto).then(() => {
            const btn = event.currentTarget;
            const originalText = btn.innerText;
            btn.innerText = "✅";
            setTimeout(() => btn.innerText = originalText, 1000);
        });
    }

    document.getElementById('buscar-cedula-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const cedula = document.getElementById('cedula-input').value;
        const contenedor = document.getElementById('resultado-cedula');

        contenedor.innerHTML = "<p style='color: white;'>Buscando...</p>";

        fetch(`../../modules/formularios/buscar_cedula.php?cedula=${cedula}`)
            .then(response => response.text())
            .then(html => {
                contenedor.innerHTML = html;
            })
            .catch(err => {
                contenedor.innerHTML = "<p style='color: red;'>Error en la búsqueda.</p>";
                console.error(err);
            });
    });
</script>