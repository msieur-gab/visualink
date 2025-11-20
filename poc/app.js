// ============================================================================
// Dexie Database Setup
// ============================================================================
const db = new Dexie('QRScannerDB');

db.version(1).stores({
    scans: '++id, timestamp, code'
});

// ============================================================================
// QR Scanner Setup (using qr-scanner library)
// ============================================================================
import QrScanner from 'https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner.min.js';

let scanner = null;
let isCameraActive = false;
let videoElement = null;

const toggleCameraBtn = document.getElementById('toggleCamera');
const cameraContainer = document.getElementById('cameraContainer');
const scannerDiv = document.getElementById('scanner');

toggleCameraBtn.addEventListener('click', toggleCamera);

async function toggleCamera() {
    if (isCameraActive) {
        await stopCamera();
    } else {
        await startCamera();
    }
}

async function startCamera() {
    try {
        // Create video element if it doesn't exist
        if (!videoElement) {
            videoElement = document.createElement('video');
            videoElement.style.width = '100%';
            videoElement.style.maxWidth = '500px';
            videoElement.style.borderRadius = '8px';
            scannerDiv.innerHTML = '';
            scannerDiv.appendChild(videoElement);
        }

        // Initialize scanner with rear camera preference
        scanner = new QrScanner(
            videoElement,
            result => onScanSuccess(result.data),
            {
                returnDetailedScanResult: true,
                highlightScanRegion: true,
                highlightCodeOutline: true,
                preferredCamera: 'environment' // Use rear camera
            }
        );

        // Start scanning
        await scanner.start();

        isCameraActive = true;
        cameraContainer.classList.remove('hidden');
        toggleCameraBtn.textContent = 'Disable Camera';
        toggleCameraBtn.classList.add('active');
        showNotification('Camera active - point at QR code', 'info');

        console.log('QR Scanner started successfully');
    } catch (err) {
        console.error('Camera error:', err);
        showNotification('Could not access camera: ' + err.message, 'error');
    }
}

async function stopCamera() {
    if (scanner) {
        try {
            scanner.stop();
            scanner.destroy();
            scanner = null;
        } catch (err) {
            console.error('Error stopping camera:', err);
        }
    }
    isCameraActive = false;
    cameraContainer.classList.add('hidden');
    toggleCameraBtn.textContent = 'Enable Camera';
    toggleCameraBtn.classList.remove('active');
}

async function onScanSuccess(decodedText) {
    // Stop camera immediately to prevent multiple scans
    if (isCameraActive) {
        console.log('QR code detected:', decodedText);
        await stopCamera();
        showNotification('QR code detected - processing...', 'success');
    }

    // Extract URL from QR code and process
    await handleScannedUrl(decodedText);
}

// ============================================================================
// Handle Scanned URLs
// ============================================================================
const urlInput = document.getElementById('urlInput');
const addManuallyBtn = document.getElementById('addManually');

addManuallyBtn.addEventListener('click', () => {
    if (urlInput.value.trim()) {
        handleScannedUrl(urlInput.value.trim());
        urlInput.value = '';
    }
});

urlInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && urlInput.value.trim()) {
        handleScannedUrl(urlInput.value.trim());
        urlInput.value = '';
    }
});

async function handleScannedUrl(url) {
    try {
        // Parse URL to extract code and origin
        const urlObj = new URL(url);
        let code = null;

        // Try to extract from /api/qr/{code} or /q/{code}
        const matches = urlObj.pathname.match(/(?:\/api\/qr\/|\/q\/)([a-zA-Z0-9_-]+)/);
        if (matches) {
            code = matches[1];
        }

        if (!code) {
            showNotification('Could not extract QR code from URL', 'error');
            return;
        }

        // Extract the origin (protocol + domain) from the scanned URL
        const apiBaseUrl = urlObj.origin;

        // Try to fetch metadata from API endpoint on the correct domain
        let metadata = null;
        try {
            // Fetch from the domain that issued the QR code
            const apiUrl = `${apiBaseUrl}/api/qr/${code}`;
            console.log('Fetching metadata from:', apiUrl);
            const response = await fetch(apiUrl);
            if (response.ok) {
                metadata = await response.json();
                console.log('Metadata fetched successfully:', metadata);
            } else {
                console.warn('Metadata fetch returned status:', response.status);
            }
        } catch (err) {
            console.warn('Could not fetch metadata:', err);
            // Continue anyway - we have the code even if metadata fails
        }

        // Store scan in Dexie
        const scan = {
            code: code,
            url: url,
            timestamp: new Date().toISOString(),
            metadata: metadata,
            userAgent: navigator.userAgent,
            scannedAt: new Date()
        };

        const id = await db.scans.add(scan);
        console.log('Scan stored with ID:', id);

        // Refresh UI
        await refreshUI();

        // Show success message
        showNotification(`Scanned: ${code}`, 'success');

    } catch (err) {
        console.error('Error handling URL:', err);
        showNotification('Error processing scan: ' + err.message, 'error');
    }
}

// ============================================================================
// UI Updates
// ============================================================================
const scansList = document.getElementById('scansList');
const scanCount = document.getElementById('scanCount');
const totalScans = document.getElementById('totalScans');
const storageUsed = document.getElementById('storageUsed');
const clearAllBtn = document.getElementById('clearAll');

clearAllBtn.addEventListener('click', async () => {
    if (confirm('Clear all scans? This cannot be undone.')) {
        await db.scans.clear();
        await refreshUI();
        showNotification('All scans cleared', 'info');
    }
});

async function refreshUI() {
    const scans = await db.scans.toArray();

    // Update count
    scanCount.textContent = scans.length;
    totalScans.textContent = scans.length;
    clearAllBtn.style.display = scans.length > 0 ? 'block' : 'none';

    // Update storage estimate
    if (navigator.storage && navigator.storage.estimate) {
        const estimate = await navigator.storage.estimate();
        const usedMB = (estimate.usage / 1024 / 1024).toFixed(2);
        storageUsed.textContent = usedMB + ' MB';
    }

    // Render scans
    if (scans.length === 0) {
        scansList.innerHTML = '<div class="empty-state"><p>No scans yet</p></div>';
        return;
    }

    scansList.innerHTML = scans
        .reverse()
        .map((scan, index) => renderScan(scan, scans.length - index))
        .join('');

    // Add delete handlers
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const scanId = parseInt(e.target.dataset.id);
            if (confirm('Delete this scan?')) {
                await db.scans.delete(scanId);
                await refreshUI();
            }
        });
    });
}

function renderScan(scan, index) {
    const timestamp = new Date(scan.timestamp);
    const timeStr = timestamp.toLocaleString();

    const targetUrl = scan.metadata?.targetUrl || 'N/A';
    const description = scan.metadata?.description || '';

    return `
        <div class="scan-card">
            <div class="scan-header">
                <span class="scan-index">#${index}</span>
                <span class="scan-code">${scan.code}</span>
                <time class="scan-time">${timeStr}</time>
            </div>

            ${description ? `<div class="scan-description">${description}</div>` : ''}

            <div class="scan-details">
                <div class="detail-row">
                    <span class="detail-label">URL:</span>
                    <a href="${targetUrl}" target="_blank" class="scan-url">${targetUrl}</a>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Scanned URL:</span>
                    <code class="scan-raw-url">${scan.url}</code>
                </div>
                ${scan.metadata?.accessCount ? `
                    <div class="detail-row">
                        <span class="detail-label">Total Scans (tracked):</span>
                        <span>${scan.metadata.accessCount}</span>
                    </div>
                ` : ''}
            </div>

            <div class="scan-actions">
                <button class="delete-btn" data-id="${scan.id}">Delete</button>
            </div>
        </div>
    `;
}

// ============================================================================
// Notifications
// ============================================================================
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================================================
// Service Worker Registration (for PWA)
// ============================================================================
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('Service Worker registered'))
        .catch(err => console.log('Service Worker registration failed:', err));
}

// ============================================================================
// Initialize
// ============================================================================
document.addEventListener('DOMContentLoaded', async () => {
    await refreshUI();
    console.log('QR Scanner POC initialized');
});
