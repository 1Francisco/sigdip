/**
 * App.js - Mobile UI Logic and Validation for SIGDIP
 */

// Configuration
const CURRENT_INSPECCION_ID = 1; // Mock current inspection session
let html5QrCode;

// Scanner Logic
const toggleScanner = () => {
    const readerDiv = document.getElementById('reader');
    if (readerDiv.style.display === 'none') {
        readerDiv.style.display = 'block';
        startScanner();
    } else {
        stopScanner();
        readerDiv.style.display = 'none';
    }
};

const startScanner = () => {
    html5QrCode = new Html5Qrcode("reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        onScanSuccess
    ).catch(err => {
        console.error("Error starting scanner:", err);
        showNotification("Error al acceder a la cámara");
    });
};

const stopScanner = () => {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode = null;
        }).catch(err => console.error("Error stopping scanner:", err));
    }
};

const onScanSuccess = (decodedText, decodedResult) => {
    console.log(`Scan result: ${decodedText}`, decodedResult);
    
    if (validateArete(decodedText)) {
        showNotification(`Arete escaneado: ${decodedText}`);
        // Highlight the animal in the list or automatically open inspection
        highlightAnimal(decodedText);
        stopScanner();
        document.getElementById('reader').style.display = 'none';
    } else {
        showNotification("Formato de arete no válido");
    }
};

const highlightAnimal = (arete) => {
    const cards = document.querySelectorAll('.animal-card');
    cards.forEach(card => {
        if (card.querySelector('.arete').textContent === arete) {
            card.scrollIntoView({ behavior: 'smooth' });
            card.style.border = '2px solid var(--primary)';
            setTimeout(() => card.style.border = 'none', 3000);
        }
    });
};

// Ear Tag Validation (Siniiga: Typically 10-12 digits)
const validateArete = (numero) => {
    // Regex for 10 to 12 digits
    const regex = /^\d{10,12}$/;
    return regex.test(numero);
};

const handleInspectionSubmit = async (animalId, result) => {
    const detail = {
        inspeccion_id: CURRENT_INSPECCION_ID,
        animal_id: animalId,
        resultado_prueba: result,
        timestamp: new Date().toISOString()
    };

    try {
        await saveDetailLocally(detail);
        updateStatusIndicator();
        showNotification(`Animal ${animalId} marcado como ${result}`);
    } catch (error) {
        console.error('Error saving detail:', error);
    }
};

const updateStatusIndicator = async () => {
    const indicator = document.getElementById('sync-indicator');
    const pending = await getAllPendingDetails();
    
    if (pending.length > 0) {
        indicator.textContent = `${pending.length} pendientes de envío`;
        indicator.classList.add('pending');
    } else {
        indicator.textContent = 'Todo sincronizado';
        indicator.classList.remove('pending');
    }
    
    const connectionStatus = document.getElementById('connection-status');
    if (navigator.onLine) {
        connectionStatus.textContent = 'En línea';
        connectionStatus.classList.add('online');
        connectionStatus.classList.remove('offline');
    } else {
        connectionStatus.textContent = 'Sin conexión (Offline-First)';
        connectionStatus.classList.add('offline');
        connectionStatus.classList.remove('online');
    }
};

const showNotification = (msg) => {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
};

// Initial state
window.addEventListener('load', () => {
    updateStatusIndicator();
    window.addEventListener('online', updateStatusIndicator);
    window.addEventListener('offline', updateStatusIndicator);
    window.addEventListener('sync-complete', updateStatusIndicator);
});
