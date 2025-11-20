# QR Redirect Service - Version PHP
## Spécialement pour hébergement mutualisé OVH

Cette version PHP fonctionne parfaitement sur un hébergement mutualisé OVH (ou tout autre hébergement PHP).

## 📦 Contenu

- `index.php` - Interface d'administration
- `redirect.php` - Point d'entrée pour les redirects (utilisé par les QR codes)
- `api.php` - API REST pour gérer les redirects
- `config.php` - Configuration
- `.htaccess` - URLs propres (`/q/code` au lieu de `/redirect.php?code=code`)

## 🚀 Installation sur OVH Mutualisé

### Étape 1: Upload des fichiers

1. Connectez-vous à votre FTP OVH (FileZilla, Cyberduck, ou FTP Manager)
   - Host: `ftp.votredomaine.com` ou `ftp.cluster0XX.hosting.ovh.net`
   - User: Votre login FTP
   - Pass: Votre mot de passe FTP

2. Uploadez tous les fichiers dans le dossier `www` (ou `public_html`)

### Étape 2: Configuration initiale

1. Éditez `config.php` et changez le mot de passe:
```php
define('ADMIN_PASSWORD', 'votre-mot-de-passe-sécurisé');
```

2. Créez le dossier `data`:
   - Via FTP: Créez un dossier nommé `data`
   - Permissions: 755 (lecture/écriture pour vous, lecture seule pour les autres)

### Étape 3: Test

1. Allez sur: `https://votredomaine.com/`
2. Entrez le login: `admin` et votre mot de passe
3. Créez votre premier redirect!

## 🔧 Structure des URLs

Vos QR codes pointeront vers:
```
https://votredomaine.com/q/code-ici
```

Grâce au `.htaccess`, cette URL sera automatiquement redirigée vers votre fichier JSON cible.

## 📱 Utilisation

### Créer un redirect

1. Connectez-vous à l'admin: `https://votredomaine.com/`
2. Remplissez le formulaire:
   - **Code**: `table-chene-01` (devient `/q/table-chene-01`)
   - **URL cible**: `https://votrestorage.com/specs-table.json`
   - **Description**: "Table en chêne massif"
3. Cliquez sur "Créer le redirect"
4. Générez le QR code et gravez-le dans le bois

### Modifier la destination

Quand vous voulez changer le fichier JSON cible:
1. Cliquez sur "Modifier"
2. Changez l'URL cible
3. Sauvegardez

Le QR code gravé continue de fonctionner avec la nouvelle destination!

## 🔒 Sécurité

### Protection de base (incluse)

- Authentification HTTP Basic pour l'admin
- Le fichier `redirects.json` est protégé par `.htaccess`
- Les logs ne gardent que les 100 derniers accès

### Pour renforcer (optionnel)

1. **Activer HTTPS** dans `.htaccess` (décommentez les lignes SSL)
2. **IP Whitelist** pour l'admin:
```apache
# Dans .htaccess, ajouter:
<Files "index.php">
    Order Deny,Allow
    Deny from all
    Allow from 192.168.1.1  # Votre IP
</Files>
```

3. **Changer le mot de passe régulièrement** dans `config.php`

## 📊 Statistiques d'accès

Pour chaque QR code, vous verrez:
- Nombre total d'accès
- Date du dernier accès
- Historique des 100 derniers accès avec:
  - Timestamp
  - Adresse IP
  - User Agent (type d'appareil)

## 🗂️ Héberger vos fichiers JSON

Plusieurs options compatibles avec OVH:

### Option 1: Dossier `files` sur votre hébergement
```
/www/files/table-chene.json
URL: https://votredomaine.com/files/table-chene.json
```

### Option 2: AWS S3 / Cloudflare R2
Hébergement de fichiers externe, très fiable

### Option 3: OVH Object Storage
Si vous avez un compte OVH, vous pouvez utiliser leur Object Storage

### Option 4: GitHub (si publique)
```
https://raw.githubusercontent.com/user/repo/main/data.json
```

## 🔧 Dépannage

### Erreur 500
- Vérifiez les permissions du dossier `data` (755)
- Vérifiez que le fichier `redirects.json` peut être créé/modifié

### Authentification ne fonctionne pas
- OVH peut nécessiter une config spéciale dans `.htaccess`:
```apache
# Si l'auth ne fonctionne pas, ajoutez:
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

### URLs propres ne marchent pas
- Vérifiez que mod_rewrite est activé (devrait l'être sur OVH)
- Assurez-vous que `.htaccess` est bien uploadé

### Le QR code ne fonctionne pas
1. Testez l'URL manuellement dans un navigateur
2. Vérifiez que le code existe dans l'admin
3. Vérifiez les logs d'accès OVH

## 💾 Sauvegarde

**Important**: Le fichier `data/redirects.json` contient tout!

Sauvegarde régulière recommandée:
1. Téléchargez `data/redirects.json` via FTP chaque semaine
2. Ou configurez une tâche cron OVH pour copier le fichier

## 📝 Exemple de fichier JSON produit

```json
{
  "nom": "Table en chêne massif",
  "dimensions": {
    "longueur": "200cm",
    "largeur": "100cm",
    "hauteur": "75cm"
  },
  "matériau": "Chêne massif français",
  "finition": "Huile naturelle",
  "entretien": "Huiler 1-2 fois par an",
  "prix": "1200€",
  "boutique": "https://votreboutique.com/table-chene"
}
```

## 🌍 Hébergement OVH - Détails techniques

Testé et compatible avec:
- OVH Perso
- OVH Pro
- OVH Performance
- PHP 7.4, 8.0, 8.1, 8.2

Ressources utilisées:
- Espace disque: < 1MB (sauf vos JSON)
- Bande passante: Minimale
- CPU: Négligeable

## 🆘 Support OVH

Si vous avez des problèmes spécifiques à OVH:
1. Manager OVH > Hébergements > Votre hébergement
2. Onglet "Modules et logs"
3. Consultez les logs d'erreur

## 📞 Questions fréquentes

**Q: Puis-je héberger les JSON sur le même serveur?**
R: Oui! Créez un dossier `files` et uploadez vos JSON dedans.

**Q: Combien de QR codes puis-je créer?**
R: Illimité pratiquement. Hébergement mutualisé OVH supporte facilement 1000+ redirects.

**Q: Les stats ralentissent le site?**
R: Non, le fichier JSON est très léger même avec 100 redirects actifs.

**Q: Puis-je changer de serveur plus tard?**
R: Oui! Téléchargez simplement `redirects.json` et ré-uploadez ailleurs.

**Q: Dois-je configurer MySQL?**
R: Non! Tout fonctionne avec des fichiers JSON, pas de base de données nécessaire.

## 🎯 Philosophie wu wei

Ce système respecte le principe de non-intervention:
- Le QR gravé reste constant (élément naturel, bois)
- Seule la destination numérique s'adapte (élément fluide, données)
- Intervention minimale: juste changer une URL
- Pas de lutte contre la matière: on n'efface pas, on ne regrave pas

Le bois porte l'information permanente, le numérique porte le contenu évolutif.
