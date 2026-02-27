function cargarTabla(tipo, origen, desde, hasta, pagina) {
  const contenedorId = "tabla-" + tipo;
  const contenedor = document.getElementById(contenedorId);

  if (!contenedor) {
    console.error("No se encontró el contenedor:", contenedorId);
    return;
  }
  contenedor.style.opacity = "0.5";
  contenedor.style.pointerEvents = "none";

  const params = new URLSearchParams({
    tipo: tipo,
    origen: origen,
    desde: desde,
    hasta: hasta,
    pagina: pagina,
  });

  fetch("ajax_tablas.php?" + params.toString())
    .then((response) => {
      if (!response.ok) throw new Error("Error en la red");
      return response.text();
    })
    .then((html) => {
      contenedor.innerHTML = html;
      contenedor.style.opacity = "1";
      contenedor.style.pointerEvents = "auto";
    })
    .catch((err) => {
      console.error("Error cargando tabla:", err);
      contenedor.innerHTML =
        '<p class="error-msg">Ocurrió un error al cargar los datos.</p>';
      contenedor.style.opacity = "1";
      contenedor.style.pointerEvents = "auto";
    });
}
