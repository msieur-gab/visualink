import { LitElement, html, css } from 'https://cdn.jsdelivr.net/npm/lit@3/+esm';

// ============================================================================
// QR Stats Component
// ============================================================================
class QRStats extends LitElement {
  static properties = {
    redirects: { type: Object }
  };

  createRenderRoot() {
    // Don't use Shadow DOM - use light DOM to inherit global CSS
    return this;
  }

  render() {
    if (!this.redirects) return html``;

    const codes = Object.values(this.redirects);
    const totalScans = codes.reduce((sum, r) => sum + (r.accessCount || 0), 0);
    const recentScans = codes.filter(r => {
      if (!r.lastAccessed) return false;
      const lastAccess = new Date(r.lastAccessed);
      const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
      return lastAccess > oneDayAgo;
    }).length;

    return html`
      <div class="stats-bar">
        <div class="stat-card">
          <div class="stat-label">Total QR Codes</div>
          <div class="stat-value">${codes.length}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Scans</div>
          <div class="stat-value">${totalScans}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Recent Activity</div>
          <div class="stat-value">${recentScans}</div>
        </div>
      </div>
    `;
  }
}

// ============================================================================
// QR Create Form Component
// ============================================================================
class QRCreateForm extends LitElement {
  static properties = {
    open: { type: Boolean },
    loading: { type: Boolean },
    error: { type: String }
  };

  createRenderRoot() {
    return this;
  }

  constructor() {
    super();
    this.open = false;
    this.loading = false;
    this.error = '';
  }

  toggle() {
    this.open = !this.open;
    if (!this.open) this.error = '';
  }

  async handleSubmit(e) {
    e.preventDefault();
    this.loading = true;
    this.error = '';

    const form = e.target;
    const code = form.code.value.trim();
    const targetUrl = form.url.value.trim();
    const description = form.description.value.trim();

    if (!code || !targetUrl) {
      this.error = 'Code and URL are required';
      this.loading = false;
      return;
    }

    try {
      const response = await fetch(this.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Basic ${this.auth}`
        },
        body: JSON.stringify({ code, targetUrl, description })
      });

      if (!response.ok) {
        const data = await response.json();
        throw new Error(data.error || 'Failed to create QR code');
      }

      form.reset();
      this.open = false;
      this.dispatchEvent(new CustomEvent('created'));
    } catch (err) {
      this.error = err.message;
    } finally {
      this.loading = false;
    }
  }

  render() {
    return html`
      <div class="create-form-section">
        <button class="create-form-toggle" @click=${() => this.toggle()}>
          <span class="create-form-toggle-icon">${this.open ? '−' : '+'}</span>
          Create New QR Code
        </button>

        ${this.open ? html`
          <form class="create-form" @submit=${this.handleSubmit}>
            <div class="form-group">
              <label for="code">Code</label>
              <input type="text" id="code" name="code" placeholder="e.g., table-oak-01" required>
            </div>

            <div class="form-group">
              <label for="url">Target URL</label>
              <input type="url" id="url" name="url" placeholder="https://example.com/file.json" required>
            </div>

            <div class="form-group">
              <label for="description">Description (optional)</label>
              <textarea id="description" name="description" placeholder="Describe this QR code..."></textarea>
            </div>

            ${this.error ? html`
              <div style="padding: 10px; background-color: #fee2e2; color: #991b1b; border-radius: 4px; margin-bottom: 16px; font-size: 13px;">
                ${this.error}
              </div>
            ` : ''}

            <div style="display: flex; gap: 8px;">
              <button type="submit" ?disabled=${this.loading}>
                ${this.loading ? html`<span class="loading"></span>` : 'Create'}
              </button>
              <button type="button" class="secondary" @click=${() => this.toggle()}>Cancel</button>
            </div>
          </form>
        ` : ''}
      </div>
    `;
  }
}

// ============================================================================
// QR Card Component
// ============================================================================
class QRCard extends LitElement {
  static properties = {
    code: { type: String },
    data: { type: Object }
  };

  createRenderRoot() {
    return this;
  }

  getRelativeTime(dateString) {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
  }

  async handleDelete() {
    if (!confirm(`Delete "${this.code}"? This cannot be undone.`)) return;

    try {
      const response = await fetch(`${this.apiUrl}/${this.code}`, {
        method: 'DELETE',
        headers: { 'Authorization': `Basic ${this.auth}` }
      });

      if (!response.ok) throw new Error('Failed to delete');
      this.dispatchEvent(new CustomEvent('deleted'));
    } catch (err) {
      alert(`Error: ${err.message}`);
    }
  }

  handleEdit() {
    this.dispatchEvent(new CustomEvent('edit', { detail: { code: this.code, data: this.data } }));
  }

  viewQR() {
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(`${this.baseUrl}/q/${this.code}`)}`;
    window.open(qrUrl, '_blank');
  }

  render() {
    if (!this.data) return html``;

    const { targetUrl, description, accessCount, currentUrlScans, lastAccessed, urlHistory } = this.data;

    return html`
      <div class="qr-card">
        <div class="qr-card-code">${this.code}</div>
        <div class="qr-card-url" title="${targetUrl}">${targetUrl}</div>
        ${description ? html`<div class="qr-card-description">${description}</div>` : ''}

        <div class="qr-card-stats">
          <div class="qr-card-stat">
            <div class="qr-card-stat-label">Total Scans</div>
            <div class="qr-card-stat-value">${accessCount}</div>
          </div>
          <div class="qr-card-stat">
            <div class="qr-card-stat-label">URL Scans</div>
            <div class="qr-card-stat-value">${currentUrlScans}</div>
          </div>
        </div>

        <div class="qr-card-meta">
          Last accessed: ${this.getRelativeTime(lastAccessed)}
          ${urlHistory && urlHistory.length > 1 ? html`
            <br>${urlHistory.length} URL versions
          ` : ''}
        </div>

        <div class="qr-card-actions">
          <button class="secondary" @click=${() => this.viewQR()}>View QR</button>
          <button class="secondary" @click=${() => this.handleEdit()}>Edit</button>
          <button class="danger" @click=${() => this.handleDelete()}>Delete</button>
        </div>
      </div>
    `;
  }
}

// ============================================================================
// Main QR Dashboard Component
// ============================================================================
class QRDashboard extends LitElement {
  static properties = {
    apiUrl: { type: String, attribute: 'api-url' },
    pollInterval: { type: Number, attribute: 'poll-interval' },
    redirects: { type: Object },
    loading: { type: Boolean },
    error: { type: String }
  };

  createRenderRoot() {
    return this;
  }

  constructor() {
    super();
    this.redirects = null;
    this.loading = true;
    this.error = '';
    this.pollInterval = 10000;
    this.pollTimer = null;
    this.auth = btoa(`Admin1234:AdminPassword1234!@#$`);
    // Dynamic base URL - auto-detect from current location
    this.baseUrl = `${window.location.protocol}//${window.location.host}`;
  }

  connectedCallback() {
    super.connectedCallback();
    this.fetchRedirects();
    this.startPolling();
  }

  disconnectedCallback() {
    super.disconnectedCallback();
    if (this.pollTimer) clearInterval(this.pollTimer);
  }

  startPolling() {
    if (this.pollTimer) clearInterval(this.pollTimer);
    this.pollTimer = setInterval(() => this.fetchRedirects(), this.pollInterval);
  }

  async fetchRedirects() {
    try {
      const response = await fetch(this.apiUrl, {
        headers: { 'Authorization': `Basic ${this.auth}` }
      });

      if (!response.ok) throw new Error('Failed to fetch');
      this.redirects = await response.json();
      this.error = '';
      this.loading = false;
    } catch (err) {
      this.error = `Error loading QR codes: ${err.message}`;
      this.loading = false;
    }
  }

  handleCreated() {
    this.fetchRedirects();
  }

  handleDeleted() {
    this.fetchRedirects();
  }

  handleEdit(e) {
    const { code, data } = e.detail;
    const newUrl = prompt('Enter new target URL:', data.targetUrl);
    if (!newUrl || newUrl === data.targetUrl) return;

    this.updateRedirect(code, newUrl);
  }

  async updateRedirect(code, newUrl) {
    try {
      const response = await fetch(`${this.apiUrl}/${code}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Basic ${this.auth}`
        },
        body: JSON.stringify({ targetUrl: newUrl })
      });

      if (!response.ok) throw new Error('Failed to update');
      this.fetchRedirects();
    } catch (err) {
      alert(`Error: ${err.message}`);
    }
  }

  render() {
    return html`
      <div class="dashboard-container">
        <div class="dashboard-header">
          <h1>QR Redirect Manager</h1>
          <p>Manage your QR code redirects and track scans</p>
        </div>

        ${this.error ? html`
          <div style="padding: 16px; background-color: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 24px;">
            ${this.error}
          </div>
        ` : ''}

        <qr-stats .redirects=${this.redirects}></qr-stats>

        <qr-create-form
          .apiUrl=${this.apiUrl}
          .auth=${this.auth}
          @created=${() => this.handleCreated()}>
        </qr-create-form>

        ${this.loading ? html`
          <div style="text-align: center; padding: 40px;">
            <div class="loading" style="margin: 0 auto; width: 24px; height: 24px;"></div>
          </div>
        ` : !this.redirects || Object.keys(this.redirects).length === 0 ? html`
          <div class="empty-state">
            <div class="empty-state-icon">∅</div>
            <h2>No QR codes yet</h2>
            <p>Create your first QR code to get started</p>
          </div>
        ` : html`
          <div class="qr-cards-grid">
            ${Object.entries(this.redirects).map(([code, data]) => html`
              <qr-card
                .code=${code}
                .data=${data}
                .apiUrl=${this.apiUrl}
                .auth=${this.auth}
                .baseUrl=${this.baseUrl}
                @edit=${(e) => this.handleEdit(e)}
                @deleted=${() => this.handleDeleted()}>
              </qr-card>
            `)}
          </div>
        `}
      </div>
    `;
  }
}

// ============================================================================
// Register Custom Elements
// ============================================================================
customElements.define('qr-dashboard', QRDashboard);
customElements.define('qr-stats', QRStats);
customElements.define('qr-create-form', QRCreateForm);
customElements.define('qr-card', QRCard);
