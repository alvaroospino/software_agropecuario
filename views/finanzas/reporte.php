<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/auth.php';
// Requerir login
requireLogin();
$db = new Database();

// Parámetros de filtrado
$tipo = $_GET['tipo'] ?? '';
$desde = $_GET['desde'] ?? (date('Y') . '-01-01'); // Por defecto desde inicio del año actual
$hasta = $_GET['hasta'] ?? date('Y-m-d'); // Por defecto hasta hoy
$agruparPor = $_GET['agrupar_por'] ?? 'mes'; // Opciones: dia, mes, categoria

// Construir consulta SQL para el reporte
$sqlWhere = "WHERE 1=1";
$params = [];

if (!empty($tipo)) {
    $sqlWhere .= " AND tipo = ?";
    $params[] = $tipo;
}

if (!empty($desde)) {
    $sqlWhere .= " AND fecha >= ?";
    $params[] = $desde;
}

if (!empty($hasta)) {
    $sqlWhere .= " AND fecha <= ?";
    $params[] = $hasta;
}

// Agrupar resultados según parámetro
$sqlGroupBy = "";
$sqlSelect = "";

switch ($agruparPor) {
    case 'dia':
        $sqlSelect = "fecha as periodo, SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos, 
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos";
        $sqlGroupBy = "GROUP BY fecha ORDER BY fecha ASC";
        break;
    case 'mes':
        $sqlSelect = "DATE_FORMAT(fecha, '%Y-%m') as periodo, 
                    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos, 
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos";
        $sqlGroupBy = "GROUP BY DATE_FORMAT(fecha, '%Y-%m') ORDER BY periodo ASC";
        break;
    case 'categoria':
        // Modificación para obtener datos agrupados por categoría y calcular ingresos y gastos juntos
        $sqlSelect = "categoria as periodo, 
                   SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                   SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos";
        $sqlGroupBy = "GROUP BY categoria ORDER BY categoria ASC";
        break;
    default:
        $sqlSelect = "DATE_FORMAT(fecha, '%Y-%m') as periodo, 
                    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos, 
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos";
        $sqlGroupBy = "GROUP BY DATE_FORMAT(fecha, '%Y-%m') ORDER BY periodo ASC";
}

$sql = "SELECT $sqlSelect FROM transacciones $sqlWhere $sqlGroupBy";
$reporte = $db->select($sql, $params);

// Calcular totales
$sqlTotales = "SELECT 
    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as total_gastos
    FROM transacciones $sqlWhere";
$totales = $db->select($sqlTotales, $params);

include '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Reporte Financiero</h4>
                        <div>
                            <button class="btn btn-success" onclick="exportarExcel()">Exportar a Excel</button>
                            <button class="btn btn-danger ms-2" onclick="exportarPDF()">Exportar a PDF</button>
                            <a href="lista.php" class="btn btn-secondary ms-2">Volver</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Filtros</h5>
                            <form method="GET" action="" class="row g-3">
                                <div class="col-md-2">
                                    <label for="tipo" class="form-label">Tipo</label>
                                    <select name="tipo" id="tipo" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="ingreso" <?php echo $tipo === 'ingreso' ? 'selected' : ''; ?>>Ingreso</option>
                                        <option value="gasto" <?php echo $tipo === 'gasto' ? 'selected' : ''; ?>>Gasto</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="desde" class="form-label">Desde</label>
                                    <input type="date" name="desde" id="desde" class="form-control" value="<?php echo htmlspecialchars($desde); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="hasta" class="form-label">Hasta</label>
                                    <input type="date" name="hasta" id="hasta" class="form-control" value="<?php echo htmlspecialchars($hasta); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="agrupar_por" class="form-label">Agrupar por</label>
                                    <select name="agrupar_por" id="agrupar_por" class="form-select">
                                        <option value="dia" <?php echo $agruparPor === 'dia' ? 'selected' : ''; ?>>Día</option>
                                        <option value="mes" <?php echo $agruparPor === 'mes' ? 'selected' : ''; ?>>Mes</option>
                                        <option value="categoria" <?php echo $agruparPor === 'categoria' ? 'selected' : ''; ?>>Categoría</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Generar Reporte</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Ingresos Totales</h5>
                                    <h3 class="card-text">$<?php echo number_format($totales[0]['total_ingresos'] ?? 0, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Gastos Totales</h5>
                                    <h3 class="card-text">$<?php echo number_format($totales[0]['total_gastos'] ?? 0, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Balance</h5>
                                    <h3 class="card-text">$<?php echo number_format(($totales[0]['total_ingresos'] ?? 0) - ($totales[0]['total_gastos'] ?? 0), 2); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráfico -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Gráfico de Resultados</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="reporteChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabla de resultados -->
                    <div class="table-responsive" id="reporteData">
                        <table class="table table-striped" id="tablaReporte">
                            <thead>
                                <tr>
                                    <th>Período</th>
                                    <th>Ingresos</th>
                                    <th>Gastos</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reporte)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No hay datos para mostrar</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reporte as $r): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                if ($agruparPor === 'dia') {
                                                    echo htmlspecialchars(date('d/m/Y', strtotime($r['periodo'])));
                                                } elseif ($agruparPor === 'mes') {
                                                    echo htmlspecialchars(date('m/Y', strtotime($r['periodo'] . '-01')));
                                                } else {
                                                    echo htmlspecialchars($r['periodo']);
                                                }
                                                ?>
                                            </td>
                                            <td>$<?php echo number_format($r['ingresos'], 2); ?></td>
                                            <td>$<?php echo number_format($r['gastos'], 2); ?></td>
                                            <td class="<?php echo ($r['ingresos'] - $r['gastos']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                $<?php echo number_format($r['ingresos'] - $r['gastos'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos y exportación -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datos para el gráfico
        <?php if (!empty($reporte)): ?>
            // Preparar datos para el gráfico
            const labels = <?php 
                if ($agruparPor === 'dia') {
                    echo json_encode(array_map(function($r) {
                        return date('d/m/Y', strtotime($r['periodo']));
                    }, $reporte));
                } elseif ($agruparPor === 'mes') {
                    echo json_encode(array_map(function($r) {
                        return date('m/Y', strtotime($r['periodo'] . '-01'));
                    }, $reporte));
                } else {
                    echo json_encode(array_map(function($r) {
                        return $r['periodo'];
                    }, $reporte));
                }
            ?>;
            
            const dataIngresos = <?php echo json_encode(array_map(function($r) {
                return floatval($r['ingresos']);
            }, $reporte)); ?>;
            
            const dataGastos = <?php echo json_encode(array_map(function($r) {
                return floatval($r['gastos']);
            }, $reporte)); ?>;
            
            const dataBalance = dataIngresos.map((ingreso, i) => ingreso - dataGastos[i]);
            
            // Crear gráfico
            const ctx = document.getElementById('reporteChart').getContext('2d');
            
            // Tipo de gráfico según agrupamiento
            const tipoGrafico = <?php echo json_encode($agruparPor === 'categoria' ? 'bar' : 'line'); ?>;
            
            const myChart = new Chart(ctx, {
                type: tipoGrafico,
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: dataIngresos,
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 2,
                            fill: false
                        },
                        {
                            label: 'Gastos',
                            data: dataGastos,
                            backgroundColor: 'rgba(220, 53, 69, 0.2)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 2,
                            fill: false
                        },
                        {
                            label: 'Balance',
                            data: dataBalance,
                            backgroundColor: 'rgba(13, 110, 253, 0.2)',
                            borderColor: 'rgba(13, 110, 253, 1)',
                            borderWidth: 2,
                            fill: false
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: <?php 
                                if ($agruparPor === 'dia') {
                                    echo "'Evolución Financiera por Día'";
                                } elseif ($agruparPor === 'mes') {
                                    echo "'Evolución Financiera por Mes'";
                                } else {
                                    echo "'Ingresos y Gastos por Categoría'";
                                }
                            ?>
                        }
                    }
                }
            });
        <?php endif; ?>
    });

    // Función para exportar a Excel
    function exportarExcel() {
        const table = document.getElementById('tablaReporte');
        const wb = XLSX.utils.table_to_book(table, {sheet: "Reporte Financiero"});
        XLSX.writeFile(wb, 'reporte_financiero_<?php echo date('Y-m-d'); ?>.xlsx');
    }
    
    // Función para exportar a PDF
    function exportarPDF() {
        // Seleccionar solo la tabla y el resumen para el PDF
        const element = document.getElementById('reporteData');
        
        // Opciones para el PDF
        const opt = {
            margin: 10,
            filename: 'reporte_financiero_<?php echo date('Y-m-d'); ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };
        
        // Capturar los totales
        const totales = document.createElement('div');
        totales.innerHTML = `
            <div style="margin-bottom: 15px; font-family: Arial, sans-serif;">
                <h3>Resumen Financiero</h3>
                <p><strong>Ingresos Totales:</strong> $<?php echo number_format($totales[0]['total_ingresos'] ?? 0, 2); ?></p>
                <p><strong>Gastos Totales:</strong> $<?php echo number_format($totales[0]['total_gastos'] ?? 0, 2); ?></p>
                <p><strong>Balance:</strong> $<?php echo number_format(($totales[0]['total_ingresos'] ?? 0) - ($totales[0]['total_gastos'] ?? 0), 2); ?></p>
            </div>
        `;
        
        // Crear un contenedor temporal para unir los totales y la tabla
        const tempContainer = document.createElement('div');
        tempContainer.appendChild(totales);
        tempContainer.appendChild(element.cloneNode(true));
        
        // Exportar a PDF
        html2pdf().set(opt).from(tempContainer).save();
    }
</script>

<?php include '../../includes/footer.php'; ?>