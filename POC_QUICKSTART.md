# QR Scanner POC - Quick Start Guide

## What Was Created

A complete proof-of-concept PWA (Progressive Web App) for testing how apps and developers interact with QR code redirect services.

## Files Added

```
qr-redirect-php/
├── api-qr.php              # NEW: JSON API endpoint (no auth required)
├── poc/                     # NEW: POC application folder
│   ├── index.html          # Main interface
│   ├── app.js              # QR scanner + Dexie.js logic
│   ├── style.css           # shadcn-inspired styling
│   ├── manifest.json       # PWA manifest
│   ├── sw.js              # Service Worker (offline support)
│   └── README.md           # Detailed documentation
├── router.php              # UPDATED: Added /api/qr/* route
└── PERMANENCE.md           # Documentation for QR code permanence solution
```

## Access the POC

Open in browser or mobile device:

```
http://192.168.86.30:8000/poc/
```

Or via localhost:
```
http://localhost:8000/poc/
```

## How It Works

### 1. Scan QR Codes
- Enable camera → real-time QR detection
- Or paste URLs manually
- Both `/q/{code}` and `/api/qr/{code}` formats supported

### 2. Store Locally
- All scans saved in browser's IndexedDB (Dexie.js)
- Persists across browser sessions
- Works offline

### 3. Fetch Metadata
- POC automatically calls `/api/qr/{code}` endpoint
- Gets target URL, access counts, history
- Displays in scan history

### 4. View History
- See all scanned QR codes
- Shows metadata from backend
- Delete individual scans or clear all

## Test with Existing QR Codes

You already have test data in the system:

```bash
# Create a test QR code via dashboard
http://192.168.86.30:8000/index-modern.php
# Login: Admin1234 / AdminPassword1234!@#$
# Create a new code

# Then scan it in POC
http://192.168.86.30:8000/poc/
```

## Test the API Endpoint

The new `/api/qr/{code}` endpoint returns metadata:

```bash
# Get metadata for existing QR code
curl -s http://localhost:8000/api/qr/tea001 | jq .
```

Response:
```json
{
  "success": true,
  "code": "tea001",
  "targetUrl": "https://www.palmer-dinnerware.com/",
  "description": "Table wear",
  "accessCount": 8,
  "lastAccessed": "2025-11-20T10:45:02+00:00",
  "urlHistory": [
    {
      "url": "https://www.siteinspire.com/",
      "startDate": "2025-11-20T08:48:57+00:00",
      "endDate": "2025-11-20T08:54:07+00:00",
      "scans": 3
    },
    {
      "url": "https://www.palmer-dinnerware.com/",
      "startDate": "2025-11-20T08:54:07+00:00",
      "endDate": null,
      "scans": 5
    }
  ],
  "qrUrl": "http://192.168.86.30:8000/q/tea001",
  "metadata": {
    "version": "1.0",
    "type": "redirect"
  }
}
```

## Key Features

✅ **Camera-based QR scanning** (html5-qrcode library)
✅ **Local storage** (IndexedDB via Dexie.js)
✅ **Offline-capable** (Service Worker caching)
✅ **PWA installable** (Add to Home Screen on mobile)
✅ **API integration** (Fetches metadata from backend)
✅ **Minimal design** (shadcn-inspired styling)
✅ **Mobile-first** (Fully responsive)
✅ **Dark mode** (Auto-detects system preference)

## What This Answers

1. ✅ **How do PWAs detect QR codes?**
   - Using html5-qrcode library with camera access
   - Simple camera permission flow

2. ✅ **How is scanned data stored?**
   - IndexedDB via Dexie.js wrapper
   - Includes timestamp, metadata, user agent
   - Persists across sessions

3. ✅ **How do apps fetch metadata?**
   - Simple HTTP GET to `/api/qr/{code}`
   - JSON response with all relevant data
   - CORS-enabled (no auth required)

4. ✅ **What info should the API return?**
   - Target URL
   - Description
   - Access counts
   - History of URL changes
   - Timestamps

5. ✅ **Can scans work offline?**
   - Yes! Service Worker caches everything
   - Manual input works without internet
   - Metadata fetches when online

## Integration Patterns for Developers

### Pattern 1: Simple Redirect (Current)
```
User scans → /q/{code} → HTTP redirect to target URL
```
✅ Works for websites, PWAs in browser

### Pattern 2: App Integration
```
App scans → calls /api/qr/{code} → downloads file → processes data
```
✅ Works for native apps, custom integrations

### Pattern 3: Hybrid (POC demonstrates this)
```
PWA scans → stores locally → fetches metadata → shows rich info
```
✅ Works for web apps, offline-first apps

## Next Steps

### Test It
1. Open POC on mobile device
2. Enable camera
3. Scan a QR code from the dashboard
4. See scan appear in history with metadata

### Use It
The POC can be deployed as-is to gather feedback:
- Which fields do developers need?
- What metadata is missing?
- How should files be delivered?

### Extend It
Based on learnings, could add:
- User accounts + sync to server
- File download handling
- Deep linking to native apps
- Analytics/reporting
- QR code generation

## Architecture Notes

### No Database Required
- Everything stored locally in IndexedDB
- No server-side scan tracking (unless you add it)
- Scales infinitely (per device)

### Public API
- `/api/qr/{code}` requires **NO authentication**
- Any app can call it
- CORS enabled for web apps

### Open Design
- PWA code is client-side (HTML/JS)
- Easy to customize for your needs
- Can be deployed anywhere

## Questions Answered By This POC

**"How do we support multiple developers/apps scanning our QR codes?"**
→ Provide the simple `/api/qr/{code}` endpoint + documentation

**"What should apps be able to do with QR codes?"**
→ POC shows the most common patterns

**"Should we track who scanned what?"**
→ Currently app-side only (local storage), but backend can log IP/UA

**"How do we handle offline scanning?"**
→ Service Worker + IndexedDB = works without internet

**"Do different QR codes need different behaviors?"**
→ Yes! Could add `type` or `action` field to API response

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Camera not working | Check browser permissions (Settings → Camera) |
| API endpoint 404 | Verify router.php has `/api/qr/*` route |
| Data not persisting | Check browser allows IndexedDB (Storage quota) |
| No metadata fetching | Ensure backend is reachable from POC |
| Offline not working | Requires HTTPS (or localhost for dev) |

## Files to Reference

- **POC Code**: `/poc/app.js` - Core QR scanning + Dexie logic
- **API Implementation**: `/api-qr.php` - What developers will call
- **Routing**: `router.php` - How URLs are mapped
- **Detailed Docs**: `/poc/README.md` - Full documentation

---

**Created**: November 20, 2025
**Purpose**: Test QR code integration patterns before production deployment
