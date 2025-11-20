# QR Scanner POC

**Proof of Concept**: Test how apps and PWAs interact with QR code redirect services.

## What This Tests

This POC explores:

1. **QR Code Scanning**: Mobile camera-based QR code detection
2. **Local Storage**: Dexie.js (IndexedDB) for offline-first data storage
3. **API Integration**: How apps fetch metadata from `/api/qr/{code}` endpoints
4. **PWA Capabilities**: Installable web app with offline support
5. **Integration Patterns**: Different ways apps could consume QR data

## Features

### Scanner
- **Camera input**: Real-time QR code detection using `html5-qrcode` library
- **Manual input**: Paste URLs directly for testing
- **Code extraction**: Automatically extracts code from URLs like:
  - `https://qr.yourdomain.com/q/{code}`
  - `https://qr.yourdomain.com/api/qr/{code}`

### Storage
- **Dexie.js**: Lightweight IndexedDB wrapper
- **Offline-first**: All scans stored locally on device
- **Automatic sync**: Fetches metadata from backend when available

### Metadata Fetching
When a QR code is scanned, the app attempts to fetch:
```
GET /api/qr/{code}
```

Response includes:
```json
{
  "code": "tea001",
  "targetUrl": "https://www.palmer-dinnerware.com/",
  "description": "Table wear",
  "accessCount": 7,
  "lastAccessed": "2025-11-20T09:19:44+00:00",
  "qrUrl": "https://qr.yourdomain.com/q/tea001"
}
```

### Data Collected Per Scan
```javascript
{
  id: 1,                              // Auto-increment ID
  code: "tea001",                     // QR code identifier
  url: "https://...",                 // Raw scanned URL
  timestamp: "2025-11-20T...",        // When scanned (ISO 8601)
  metadata: { ... },                  // API response (if available)
  userAgent: "Mozilla/5.0 ...",       // Device info
  scannedAt: Date object              // JS date object
}
```

## How to Use

### 1. Start the Server

```bash
cd /home/msieur-gab/qr-redirect-php
php -S 0.0.0.0:8000 router.php
```

### 2. Access the POC

Open browser or mobile device:
```
http://192.168.86.30:8000/poc/
```

### 3. Test Scanning

**Option A: Scan Real QR Code**
1. Create a QR code via dashboard: `http://192.168.86.30:8000/index-modern.php`
2. Open POC on mobile device
3. Click "Enable Camera"
4. Point phone at QR code

**Option B: Manual Input**
1. Paste QR URL directly:
   - `https://qr.yourdomain.com/api/qr/tea001`
   - Or raw: `http://192.168.86.30:8000/q/tea001`
2. Click "Add"

### 4. View Scans

- All scans appear in "Scanned QR Codes" section
- Shows metadata fetched from backend
- Click "Delete" to remove individual scans
- "Clear All" removes all scans

## Architecture

### Directory Structure
```
poc/
├── index.html          # Main PWA interface
├── app.js             # QR scanner + Dexie.js logic
├── style.css          # shadcn-inspired styling
├── manifest.json      # PWA manifest
├── sw.js             # Service Worker (offline support)
└── README.md         # This file
```

### Libraries Used

1. **html5-qrcode** (CDN)
   - Camera-based QR code detection
   - Handles browser permissions
   - Works on all modern mobile phones

2. **Dexie.js** (CDN)
   - IndexedDB wrapper
   - Simple table-based storage
   - Supports offline operations

3. **Lit** (optional for future)
   - Not used in POC, but dashboard uses it
   - Could extend POC with Web Components

## Backend Integration

### Required Endpoint

The POC expects this endpoint to exist:

```php
// api-qr.php
GET /api/qr/{code}
→ Returns JSON metadata
→ CORS-enabled (no auth required)
```

Currently implemented in:
- `api-qr.php` - Handler
- `router.php` - Route: `/api/qr/{code}` → `api-qr.php`

### What the Endpoint Should Return

```json
{
  "success": true,
  "code": "tea001",
  "targetUrl": "https://...",
  "description": "...",
  "createdAt": "ISO-8601",
  "accessCount": 7,
  "lastAccessed": "ISO-8601",
  "urlHistory": [...],
  "qrUrl": "https://qr.yourdomain.com/q/tea001",
  "metadata": {
    "version": "1.0",
    "type": "redirect"
  }
}
```

## PWA Features

### Installation
On mobile (iOS/Android):
1. Open POC in browser
2. Look for "Install" or "Add to Home Screen" prompt
3. App installs alongside native apps

### Offline Capability
- All static assets cached via Service Worker
- Works without internet connection
- Can scan codes, store locally, sync when online

### Camera Permissions
- First scan triggers camera permission prompt
- Stored by browser for future sessions
- User can revoke in settings anytime

## Testing Scenarios

### Scenario 1: Offline Scanning
1. Open POC on mobile
2. Enable offline mode (airplane mode)
3. Use manual URL input to add scans
4. Scans stored locally in Dexie
5. Re-enable internet → metadata fetches on next visit

### Scenario 2: Backend Migration
1. Scan QR codes with current backend
2. Backend migrates to new domain
3. Update `QR_BASE_URL` in config.php
4. POC continues working (URLs already cached locally)

### Scenario 3: App Integration
1. Developer scans QR code in POC
2. App extracts `code` value
3. App calls `/api/qr/{code}` endpoint
4. App downloads file from `targetUrl`
5. App processes data

## Limitations & Future Work

### Current Limitations
1. No authentication (all QR codes public)
2. No sync backend (scan history stays on device)
3. No file download handling
4. No app schema deep linking

### Potential Extensions
1. **User Accounts**: Add login to sync scans across devices
2. **File Downloads**: Direct download files from `targetUrl`
3. **App Integration**: Deep linking via `myapp://qr/{code}`
4. **Analytics**: Send scan stats back to server
5. **QR Generation**: Dynamically generate QR codes in app
6. **Batch Operations**: Export/import scan history

## Troubleshooting

### Camera Not Working
- Check browser permissions: Settings → Permissions → Camera
- Ensure HTTPS (or localhost/192.168 for development)
- Some browsers require secure context (HTTPS)

### Metadata Not Fetching
- Check backend `/api/qr/{code}` is accessible
- Verify CORS headers in `api-qr.php`
- Check browser console for fetch errors

### IndexedDB Not Saving
- Check browser storage quota (Settings → Storage)
- Try clearing cache and reload
- Use Firefox DevTools → Storage to inspect IndexedDB

## API Reference

### Scanner
```javascript
// Scan from camera
scanner.start(cameraId, config, onSuccess, onError);

// Stop camera
scanner.stop();
```

### Dexie Storage
```javascript
// Add scan
await db.scans.add(scanObject);

// Get all scans
const scans = await db.scans.toArray();

// Delete scan
await db.scans.delete(id);

// Clear all
await db.scans.clear();
```

### Fetch Metadata
```javascript
const response = await fetch(`/api/qr/${code}`);
const metadata = await response.json();
```

## Next Steps

After testing this POC, consider:

1. **Choose Integration Pattern**: Decide which approach developers need
2. **Build Backend API**: Finalize `/api/qr/{code}` specification
3. **Implement App SDK**: Create libraries for common languages/frameworks
4. **Production PWA**: Deploy official QR scanner app
5. **Analytics**: Track which codes are scanned, by whom, when

## Questions This POC Answers

1. ✅ How do PWAs detect QR codes?
2. ✅ How is data stored locally?
3. ✅ How do apps fetch metadata?
4. ✅ What info should be returned by API?
5. ✅ How does offline storage work?
6. ? Should tracking be per-app or per-code?
7. ? Do apps need authentication?
8. ? Should scans sync to server?
