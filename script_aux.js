function toggleReporte(idVenta) {
    const formulario = document.getElementById(`reporte-${idVenta}`);
    if (formulario) {
        formulario.style.display = formulario.style.display === "none" ? "block" : "none";
    }
}
