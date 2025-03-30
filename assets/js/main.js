// Función para mostrar confirmación antes de eliminar
function confirmarEliminar(event, mensaje) {
    if (!confirm(mensaje || '¿Estás seguro de que deseas eliminar este elemento?')) {
        event.preventDefault();
    }
}

// Inicialización para los tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Función para actualizar el texto de los campos de archivos
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            var fileName = this.files[0].name;
            var next = this.nextElementSibling;
            next.innerText = fileName;
        });
    });
});

// Función para generar gráficos básicos con Chart.js
function crearGraficoBarras(elementId, etiquetas, datos, titulo) {
    if (!document.getElementById(elementId)) return;
    
    const ctx = document.getElementById(elementId).getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: titulo,
                data: datos,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}