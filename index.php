<?php
require_once 'config.php';
checkAuth();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Redirect Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        h1 {
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        button.delete {
            background: #dc3545;
        }
        
        button.delete:hover {
            background: #c82333;
        }
        
        button.update {
            background: #28a745;
            margin-left: 10px;
        }
        
        button.update:hover {
            background: #218838;
        }
        
        .redirects-list {
            margin-top: 30px;
        }
        
        .redirect-item {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
        
        .redirect-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .redirect-code {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
        }
        
        .redirect-url {
            margin-bottom: 10px;
            word-break: break-all;
        }
        
        .redirect-url a {
            color: #007bff;
            text-decoration: none;
        }
        
        .redirect-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .qr-code {
            max-width: 150px;
            margin-top: 10px;
        }
        
        .edit-form {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .edit-form.active {
            display: block;
        }
        
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .qr-section {
            text-align: center;
            margin-top: 20px;
        }
        
        .generate-qr-btn {
            background: #6c757d;
        }
        
        .generate-qr-btn:hover {
            background: #5a6268;
        }

        small {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 QR Redirect Manager</h1>
        
        <div id="message"></div>
        
        <div class="form-section">
            <h2>Créer un nouveau redirect</h2>
            <form id="createForm">
                <div class="form-group">
                    <label for="code">Code court (pour QR)*</label>
                    <input type="text" id="code" required placeholder="ex: bois01, produit-a">
                    <small>URL finale: <?php echo $_SERVER['HTTP_HOST']; ?>/q/<strong>code</strong></small>
                </div>
                
                <div class="form-group">
                    <label for="targetUrl">URL du fichier JSON*</label>
                    <input type="url" id="targetUrl" required placeholder="https://example.com/data.json">
                </div>
                
                <div class="form-group">
                    <label for="description">Description (optionnel)</label>
                    <textarea id="description" placeholder="ex: Infos table en chêne"></textarea>
                </div>
                
                <button type="submit">Créer le redirect</button>
            </form>
        </div>
        
        <div class="redirects-list">
            <h2>Redirects actifs</h2>
            <div id="redirectsList"></div>
        </div>
    </div>
    
    <script>
        const API_BASE = 'api.php';
        
        function showMessage(text, type = 'success') {
            const messageDiv = document.getElementById('message');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = text;
            setTimeout(() => {
                messageDiv.className = '';
                messageDiv.textContent = '';
            }, 5000);
        }
        
        async function loadRedirects() {
            try {
                const response = await fetch(API_BASE);
                const redirects = await response.json();
                displayRedirects(redirects);
            } catch (error) {
                showMessage('Erreur de chargement: ' + error.message, 'error');
            }
        }
        
        function displayRedirects(redirects) {
            const container = document.getElementById('redirectsList');
            
            if (Object.keys(redirects).length === 0) {
                container.innerHTML = '<p style="color: #666; text-align: center;">Aucun redirect. Créez-en un ci-dessus!</p>';
                return;
            }
            
            container.innerHTML = Object.entries(redirects).map(([code, data]) => `
                <div class="redirect-item">
                    <div class="redirect-header">
                        <div>
                            <div class="redirect-code">/q/${code}</div>
                            ${data.description ? `<p style="color: #666; margin-top: 5px;">${data.description}</p>` : ''}
                        </div>
                        <div>
                            <button onclick="toggleEdit('${code}')">Modifier</button>
                            <button class="delete" onclick="deleteRedirect('${code}')">Supprimer</button>
                        </div>
                    </div>
                    
                    <div class="redirect-url">
                        <strong>Cible:</strong> <a href="${data.targetUrl}" target="_blank">${data.targetUrl}</a>
                    </div>
                    
                    <div class="redirect-stats">
                        <div class="stat-item">
                            <strong>Accès:</strong> ${data.accessCount || 0}
                        </div>
                        ${data.lastAccessed ? `
                            <div class="stat-item">
                                <strong>Dernier accès:</strong> ${new Date(data.lastAccessed).toLocaleString('fr-FR')}
                            </div>
                        ` : ''}
                        <div class="stat-item">
                            <strong>Créé:</strong> ${new Date(data.createdAt).toLocaleDateString('fr-FR')}
                        </div>
                    </div>
                    
                    <div class="qr-section">
                        <button class="generate-qr-btn" onclick="generateQR('${code}')">Générer QR Code</button>
                        <div id="qr-${code}"></div>
                    </div>
                    
                    <div id="edit-${code}" class="edit-form">
                        <h3>Modifier le redirect</h3>
                        <div class="form-group">
                            <label>Nouvelle URL cible</label>
                            <input type="url" id="edit-url-${code}" value="${data.targetUrl}">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="edit-desc-${code}">${data.description || ''}</textarea>
                        </div>
                        <button class="update" onclick="updateRedirect('${code}')">Sauvegarder</button>
                        <button onclick="toggleEdit('${code}')">Annuler</button>
                    </div>
                </div>
            `).join('');
        }
        
        async function createRedirect(event) {
            event.preventDefault();
            
            const code = document.getElementById('code').value.trim();
            const targetUrl = document.getElementById('targetUrl').value.trim();
            const description = document.getElementById('description').value.trim();
            
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code, targetUrl, description })
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    const baseUrl = '<?php echo QR_BASE_URL; ?>';
                    showMessage(`Redirect créé! URL: ${baseUrl}${result.url}`);
                    document.getElementById('createForm').reset();
                    loadRedirects();
                } else {
                    showMessage(result.error, 'error');
                }
            } catch (error) {
                showMessage('Erreur: ' + error.message, 'error');
            }
        }
        
        async function updateRedirect(code) {
            const targetUrl = document.getElementById(`edit-url-${code}`).value.trim();
            const description = document.getElementById(`edit-desc-${code}`).value.trim();
            
            try {
                const response = await fetch(`${API_BASE}/${code}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ targetUrl, description })
                });
                
                if (response.ok) {
                    showMessage('Redirect mis à jour!');
                    toggleEdit(code);
                    loadRedirects();
                } else {
                    const result = await response.json();
                    showMessage(result.error, 'error');
                }
            } catch (error) {
                showMessage('Erreur: ' + error.message, 'error');
            }
        }
        
        async function deleteRedirect(code) {
            if (!confirm(`Supprimer le redirect /q/${code}? Irréversible!`)) {
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/${code}`, {
                    method: 'DELETE'
                });
                
                if (response.ok) {
                    showMessage('Redirect supprimé');
                    loadRedirects();
                } else {
                    const result = await response.json();
                    showMessage(result.error, 'error');
                }
            } catch (error) {
                showMessage('Erreur: ' + error.message, 'error');
            }
        }
        
        function toggleEdit(code) {
            const editForm = document.getElementById(`edit-${code}`);
            editForm.classList.toggle('active');
        }
        
        function generateQR(code) {
            const container = document.getElementById(`qr-${code}`);
            const baseUrl = '<?php echo QR_BASE_URL; ?>';
            const url = `${baseUrl}/q/${code}`;
            
            container.innerHTML = `
                <p style="margin: 10px 0; font-size: 12px; color: #666;">
                    QR Code pour: ${url}
                </p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}" 
                     alt="QR Code" 
                     class="qr-code">
                <p style="margin: 10px 0; font-size: 12px;">
                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(url)}" 
                       download="qr-${code}.png" 
                       target="_blank">Télécharger Haute-Res</a>
                </p>
            `;
        }
        
        // Initialize
        document.getElementById('createForm').addEventListener('submit', createRedirect);
        loadRedirects();
    </script>
</body>
</html>
