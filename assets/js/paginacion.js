/* assets/js/paginacion.js */

/**
 * Función para cargar tablas de reporte de forma independiente.
 * Reemplaza solo el contenido del div específico (ej: #tabla-nacimiento)
 */
function cargarTabla(tipo, origen, desde, hasta, pagina) {
  // 1. Identificar el contenedor específico de ESTA tabla
  const contenedorId = "tabla-" + tipo;
  const contenedor = document.getElementById(contenedorId);

  if (!contenedor) {
    console.error("No se encontró el contenedor:", contenedorId);
    return;
  }

  // 2. Efecto visual de carga (opacidad)
  contenedor.style.opacity = "0.5";
  contenedor.style.pointerEvents = "none"; // Evita doble clic

  // 3. Preparar los datos para enviar al servidor
  const params = new URLSearchParams({
    tipo: tipo,
    origen: origen,
    desde: desde,
    hasta: hasta,
    pagina: pagina,
  });

  // 4. Petición AJAX al nuevo motor
  // Ajusta la ruta si 'modules' está en otro nivel relativo a donde se ejecuta el JS
  // Usualmente desde reportes/ es: 'ajax_tablas.php'
  fetch("ajax_tablas.php?" + params.toString())
    .then((response) => {
      if (!response.ok) throw new Error("Error en la red");
      return response.text();
    })
    .then((html) => {
      // 5. Reemplazar SOLO el contenido de esta tabla
      contenedor.innerHTML = html;

      // Restaurar estado visual
      contenedor.style.opacity = "1";
      contenedor.style.pointerEvents = "auto";

      // Opcional: Hacer scroll suave al inicio de la tabla si es muy larga
      // contenedor.scrollIntoView({ behavior: 'smooth', block: 'start' });
    })
    .catch((err) => {
      console.error("Error cargando tabla:", err);
      contenedor.innerHTML =
        '<p class="error-msg">Ocurrió un error al cargar los datos.</p>';
      contenedor.style.opacity = "1";
      contenedor.style.pointerEvents = "auto";
    });
}
