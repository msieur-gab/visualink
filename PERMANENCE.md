# QR Code Permanence Solution

## Problem
Physical QR codes engraved in wood point to a specific domain/IP. If the service migrates (e.g., `qrprod.com` → `mynewservice.xyz`), all QR codes break.

## Solution: Own Your Redirect Domain
Use a stable redirect domain that never changes. The domain proxies requests to your current backend location.

```
QR Code → qr.yourdomain.com/q/tea001 → [Current Backend] → Final Destination
         (permanent)                     (can change)
```

## Implementation

### 1. Register a Permanent Domain
- Cost: ~$10-15/year
- Recommended: `qr.yourdomain.com` or short domain like `go.yourdomain.com`
- Never use this domain for anything else

### 2. Deploy Cloudflare Worker (FREE Tier)

**Create Worker Script** (`redirect-worker.js`):
```javascript
const BACKEND_URL = 'http://192.168.86.30:8000'; // Update when backend moves

addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
  const url = new URL(request.url);

  // Proxy request to current backend
  const backendUrl = BACKEND_URL + url.pathname + url.search;

  const response = await fetch(backendUrl, {
    method: request.method,
    headers: request.headers,
    body: request.body
  });

  return response;
}
```

**Deploy**:
1. Sign up at https://dash.cloudflare.com
2. Go to Workers & Pages → Create Worker
3. Paste script above, set `BACKEND_URL` to your current backend
4. Deploy
5. Add custom domain: `qr.yourdomain.com`

### 3. Update Application Config

**config.php** (line 5):
```php
define('QR_BASE_URL', 'https://qr.yourdomain.com'); // Use permanent domain
```

**dashboard.js** (line 383):
```javascript
.baseUrl=${'https://qr.yourdomain.com'}
```

### 4. Test

1. Create new QR code via dashboard
2. Verify QR code URL uses `qr.yourdomain.com`
3. Scan QR code → should redirect properly
4. Check `data/{code}.json` → scans increment correctly

## Migration Process

When moving backend to new server:

1. Update Worker's `BACKEND_URL` to new location
2. Save and deploy Worker
3. Test: existing QR codes should work immediately
4. No changes needed to application code

## Free Tier Limits

Cloudflare Workers Free Tier:
- 100,000 requests/day
- 10ms CPU time per request
- No credit card required

For wood-engraved QR codes, this is more than sufficient.

## Alternative: Self-Hosted Redirect

If you prefer not to use Cloudflare, you can keep a minimal server running with NGINX:

```nginx
server {
    listen 80;
    server_name qr.yourdomain.com;

    location / {
        proxy_pass http://192.168.86.30:8000;  # Update when backend moves
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

This server becomes your permanent redirect point. Update `proxy_pass` when backend moves.

## Benefits

1. **True Permanence**: Physical QR codes never break
2. **Flexibility**: Move backend anytime without re-engraving
3. **Zero Downtime**: Update redirect domain, test, then switch
4. **Cost**: $10-15/year for domain (Worker is free)
5. **Control**: You own the domain forever
