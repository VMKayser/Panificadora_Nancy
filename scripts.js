function cargarContenido(abrir) {
  var contenedor;
  contenedor = document.getElementById("contenido");
  fetch(abrir)
    .then((response) => response.text())
    .then((data) => (contenedor.innerHTML = data));
}
function cargarContenidoModal(abrir) {
    fetch(abrir)
      .then((response) => response.text())
      .then((data) => {
        document.getElementById("modal-body").innerHTML = data;
        document.getElementById("modal").style.display = "block";
    });
}

function cerrarModal() {
  document.getElementById("modal").style.display = "none";
}
window.onclick = function(event) {
    var modal = document.getElementById("modal");
    if (event.target == modal) {
        cerrarModal();
    }
};

window.onclick = function(event) {
    var modal = document.getElementById("myModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
};