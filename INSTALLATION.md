# Installation Rapide - Checklist OVH Mutualisé

## ✅ Avant de commencer

- [ ] Accès FTP OVH (host, login, password)
- [ ] Nom de domaine configuré
- [ ] Client FTP installé (FileZilla recommandé)

## 📤 Étapes d'installation (10 minutes)

### 1. Upload FTP
```
1. Connectez FileZilla à votre FTP OVH
2. Naviguez vers le dossier /www ou /public_html
3. Uploadez TOUS les fichiers du projet:
   - index.php
   - redirect.php
   - api.php
   - config.php
   - .htaccess
   - data/ (le dossier entier)
```

### 2. Configuration sécurité
```
1. Éditez config.php via FileZilla (clic droit > Voir/Éditer)
2. Ligne 4: Changez le mot de passe
   define('ADMIN_PASSWORD', 'VotreMotDePasseSecurise123!');
3. Sauvegardez et ré-uploadez
```

### 3. Permissions
```
1. Clic droit sur le dossier 'data' > Permissions
2. Réglez sur: 755 (rwxr-xr-x)
3. Cochez "Appliquer récursivement"
```

### 4. Test
```
1. Ouvrez: https://votredomaine.com
2. Login: admin
3. Password: celui que vous avez défini
4. Créez un test redirect!
```

## 🎯 Premier redirect

Dans l'interface admin:
```
Code: test01
URL cible: https://example.com/test.json
Description: Test de fonctionnement

Cliquez "Créer le redirect"
```

Testez: `https://votredomaine.com/q/test01`
→ Devrait rediriger vers votre JSON!

## 🔧 Si ça ne marche pas

### Erreur "401 Unauthorized"
→ Effacez le cache de votre navigateur, réessayez

### Erreur "500 Internal Server Error"
→ Vérifiez permissions du dossier data (755)
→ Consultez les logs dans Manager OVH

### URLs avec "?code=" au lieu de "/q/"
→ Vérifiez que .htaccess est bien uploadé
→ Mode Transfert: ASCII (pas Binary)

### Authentification en boucle
→ Ajoutez dans .htaccess:
```apache
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

## 📁 Structure finale sur FTP

```
/www/
├── index.php
├── redirect.php
├── api.php
├── config.php
├── .htaccess
└── data/
    └── redirects.json
```

## 🎨 Créer vos QR codes

1. Dans l'admin, cliquez "Générer QR Code"
2. Téléchargez la version haute résolution (500x500)
3. Pour la gravure bois: imprimez d'abord sur papier pour tester!
4. Taille minimum recommandée: 2cm x 2cm

## 💡 Conseils pour la gravure

- ✅ Testez le QR sur papier AVANT de graver
- ✅ Utilisez un niveau d'erreur élevé (30%)
- ✅ Privilégiez les codes courts (moins de 10 caractères)
- ✅ Gravure foncée sur bois clair = meilleur contraste
- ✅ Évitez les zones rugueuses ou fissurées du bois

## 🔐 Sécurité post-installation

- [ ] Changé le mot de passe dans config.php
- [ ] Testé que /data/redirects.json n'est pas accessible directement
- [ ] Configuré une sauvegarde hebdomadaire du fichier redirects.json
- [ ] Activé HTTPS (si disponible sur votre plan OVH)

## 📊 Utilisation quotidienne

**Changer la destination d'un QR code:**
1. Ouvrez l'admin
2. Cliquez "Modifier" sur le redirect
3. Changez l'URL cible
4. Sauvegardez
→ Le QR gravé fonctionne immédiatement avec la nouvelle destination!

**Voir les statistiques:**
- Nombre d'accès total
- Dernier scan (date + heure)
- Historique des 100 derniers scans

## 🆘 Support

**Problème OVH spécifique:**
Manager OVH > Hébergement > Logs et statistiques

**Problème technique:**
Vérifiez le README.md pour le dépannage détaillé

## ✨ C'est tout!

Votre système est prêt. Les QR gravés dans le bois sont maintenant reliés à des fichiers JSON modifiables à volonté!
