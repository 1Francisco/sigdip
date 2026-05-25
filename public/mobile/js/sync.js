/**
 * Sync.js - Synchronization logic for SIGDIP
 */

const SYNC_ENDPOINT = '/api/inspecciones/sync';

const syncPendingData = async () => {
    if (!navigator.onLine) return;

    const pendingDetails = await getAllPendingDetails();
    if (pendingDetails.length === 0) return;

    console.log('Detectada conexión. Sincronizando datos...');

    // Group by inspection_id if necessary, or send all if they belong to the same session
    // For simplicity, we assume one active inspection session
    const inspectionId = pendingDetails[0].inspeccion_id;
    
    try {
        const response = await fetch(SYNC_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                inspeccion_id: inspectionId,
                detalles: pendingDetails.map(d => ({
                    animal_id: d.animal_id,
                    resultado_prueba: d.resultado_prueba
                }))
            })
        });

        const result = await response.json();

        if (result.success) {
            console.log('Sincronización exitosa. Limpiando datos locales.');
            await clearPendingDetails(pendingDetails.map(d => d.id));
            
            // Dispatch custom event for UI update
            window.dispatchEvent(new CustomEvent('sync-complete'));
        } else {
            console.error('Error en sincronización:', result.errors);
        }
    } catch (error) {
        console.error('Error al intentar sincronizar:', error);
    }
};

// Listen for online event
window.addEventListener('online', syncPendingData);

// Check periodically or on specific actions
setInterval(syncPendingData, 60000); // Every minute
