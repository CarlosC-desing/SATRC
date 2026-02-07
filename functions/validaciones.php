<?php
function sanear($dato)
{
    if (is_array($dato)) {
        return array_map('sanear', $dato);
    }
    // Elimina espacios, quita etiquetas HTML y convierte caracteres especiales
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}
