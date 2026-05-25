/**
 * DB.js - IndexedDB Wrapper for SIGDIP Offline-First
 */

const DB_NAME = 'SIGDIP_Offline_DB';
const DB_VERSION = 1;

const initDB = () => {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = (event) => {
            console.error('Error opening IndexedDB:', event.target.error);
            reject(event.target.error);
        };

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            // Store for pending inspection details
            if (!db.objectStoreNames.contains('pending_details')) {
                db.createObjectStore('pending_details', { keyPath: 'id', autoIncrement: true });
            }

            // Cache for animals of the current predio
            if (!db.objectStoreNames.contains('cached_animals')) {
                db.createObjectStore('cached_animals', { keyPath: 'id' });
            }
        };

        request.onsuccess = (event) => {
            resolve(event.target.result);
        };
    });
};

const saveDetailLocally = async (detail) => {
    const db = await initDB();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending_details'], 'readwrite');
        const store = transaction.objectStore('pending_details');
        const request = store.add(detail);

        request.onsuccess = () => resolve(request.result);
        request.onerror = (event) => reject(event.target.error);
    });
};

const getAllPendingDetails = async () => {
    const db = await initDB();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending_details'], 'readonly');
        const store = transaction.objectStore('pending_details');
        const request = store.getAll();

        request.onsuccess = () => resolve(request.result);
        request.onerror = (event) => reject(event.target.error);
    });
};

const clearPendingDetails = async (ids) => {
    const db = await initDB();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending_details'], 'readwrite');
        const store = transaction.objectStore('pending_details');
        
        ids.forEach(id => store.delete(id));

        transaction.oncomplete = () => resolve();
        transaction.onerror = (event) => reject(event.target.error);
    });
};
