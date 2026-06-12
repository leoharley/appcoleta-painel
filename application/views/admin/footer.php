</div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        });
        
        // Initialize DataTables
        $(document).ready(function() {
            if ($.fn.DataTable) {
                // Função para converter data brasileira em timestamp
                $.fn.dataTable.ext.type.order['date-br-pre'] = function (data) {
                    if (!data || data === '' || typeof data !== 'string') return 0;
                    
                    // Extrair apenas a parte da data/hora, ignorando HTML
                    const textContent = $('<div>').html(data).text().trim();
                    
                    // Buscar padrão dd/mm/yyyy HH:mm
                    const match = textContent.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})/);
                    if (match) {
                        const day = parseInt(match[1], 10);
                        const month = parseInt(match[2], 10) - 1; // Mês é 0-based
                        const year = parseInt(match[3], 10);
                        const hour = parseInt(match[4], 10);
                        const minute = parseInt(match[5], 10);
                        
                        return new Date(year, month, day, hour, minute).getTime();
                    }
                    
                    // Se for "Incompleto" ou similar, retornar 0
                    return 0;
                };

                // Detectar automaticamente colunas de data brasileira
                $.fn.dataTable.ext.type.detect.unshift(function (data) {
                    if (typeof data !== 'string') return null;
                    
                    const textContent = $('<div>').html(data).text().trim();
                    
                    if (textContent.match(/\d{1,2}\/\d{1,2}\/\d{4}\s+\d{1,2}:\d{2}/) || 
                        textContent.includes('Incompleto')) {
                        return 'date-br';
                    }
                    
                    return null;
                });

                // Inicializar DataTables
                $('.data-table').DataTable({
                    responsive: true,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                    },
                    order: [[5, "desc"]], // Ordenar por Data/Hora descendente (mais recente primeiro)
                    columnDefs: [
                        {
                            targets: 5, // Coluna Data/Hora
                            type: "date-br"
                        },
                        {
                            targets: 7, // Coluna Ações
                            orderable: false,
                            searchable: false
                        }
                    ],
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                });
            }
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>

</body>
</html>