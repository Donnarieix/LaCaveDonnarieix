# La Cave Donnarieix

**La Cave Donnarieix** est une application de stockage de fichiers auto-hébergée développée principalement en Rust.

Le projet propose un fonctionnement proche d'un Drive classique tout en conservant la maîtrise de l'infrastructure et des données stockées.

Il est composé de deux parties principales :

- une API Rust basée sur Axum
- un client multiplateforme basé sur Dioxus pour le Web, Windows Desktop et Android

Le stockage physique des fichiers est assuré par un NAS Synology tandis que les métadonnées applicatives sont conservées dans une base SQLite sur le serveur.

## Démonstration

La version Web déployée est accessible à l'adresse :

**https://drive.donnarieix.fr**

L'application est servie par nginx en HTTPS. Les requêtes vers l'API passent par `/api/` et sont ensuite transmises à l'API Axum exécutée sur le serveur.

## Fonctionnalités principales

La Cave Donnarieix permet notamment de :

- créer et organiser des fichiers et dossiers
- uploader et télécharger des fichiers volumineux
- prévisualiser plusieurs types de fichiers
- rechercher des ressources
- gérer des favoris et des dossiers épinglés
- déplacer, copier et renommer des ressources
- utiliser une corbeille avec restauration
- partager des fichiers ou dossiers en privé, en accès restreint ou publiquement
- gérer un quota de stockage par utilisateur
- demander une augmentation de quota
- administrer les comptes et les inscriptions
- utiliser l'application depuis le Web, Windows Desktop et Android
- utiliser un thème clair, sombre ou synchronisé avec le système

Les transferts de fichiers sont réalisés en streaming afin de permettre la manipulation de fichiers de plusieurs dizaines de gigaoctets sans charger leur contenu complet en mémoire.

## Architecture

```text
                         Clients
                Web / Desktop / Android
                          │
                        HTTPS
                          │
                          ▼
                drive.donnarieix.fr
                          │
                        nginx
                   ┌──────┴──────┐
                   │             │
                   ▼             ▼
             Client Web        /api/
              Dioxus             │
             statique            ▼
                            API Axum
                               │
                    ┌──────────┴──────────┐
                    │                     │
                    ▼                     ▼
                  SQLite                 NFS
                    │                     │
                    ▼                     ▼
             Métadonnées             NAS Synology
```

L'API suit une architecture séparant les responsabilités :

```text
HTTP / Axum
     │
     ▼
DriveService
     │
     ├── MetadataRepository
     │        │
     │        ▼
     │   SqliteRepository
     │        │
     │        ▼
     │     SQLite
     │
     └── Storage
              │
              ▼
          NasStorage
              │
              ▼
         NAS via NFS
```

## Organisation du dépôt

Ce dépôt central regroupe les deux parties principales du projet sous forme de submodules Git.

```text
LaCaveDonnarieix/
├── api/
│   └── LaCaveDonnarieixApi
│
├── clients/
│   └── LaCaveDonnarieixClient
│
└── README.md
```

### API

Le submodule `api/` contient le backend de l'application.

Technologies principales :

- Rust
- Axum
- Tokio
- SQLx
- SQLite
- JWT
- Argon2id
- SHA-256
- systemd
- NFS

Le backend gère notamment :

- l'authentification
- les utilisateurs
- les fichiers et dossiers
- les autorisations
- les partages
- les quotas
- les uploads et téléchargements
- la persistance des métadonnées
- la communication avec le stockage physique
- les opérations d'administration

Le dépôt correspondant est :

[LaCaveDonnarieixApi](https://github.com/LuDoSniper/LaCaveDonnarieixApi)

### Clients

Le submodule `clients/` contient le client Dioxus partagé entre les différentes plateformes.

Technologies principales :

- Rust
- Dioxus 0.7
- WebAssembly pour le Web
- WebView natif pour Desktop et Android
- CSS responsive

Trois cibles principales sont supportées :

- Web
- Windows Desktop
- Android

Le dépôt correspondant est :

[LaCaveDonnarieixClient](https://github.com/LuDoSniper/LaCaveDonnarieixClient)

## Récupérer le projet

Les deux parties du projet étant intégrées sous forme de submodules, le dépôt doit être cloné avec :

```bash
git clone --recurse-submodules https://github.com/LuDoSniper/LaCaveDonnarieix.git
```

Si le dépôt a déjà été cloné sans les submodules :

```bash
git submodule update --init --recursive
```

Pour récupérer les dernières versions référencées par le dépôt principal :

```bash
git pull
git submodule update --init --recursive
```

## Développement

Chaque submodule possède son propre README avec les instructions détaillées de développement et de compilation.

### API

```bash
cd api
cargo run
```

Les principales validations sont :

```bash
cargo fmt --all -- --check
cargo clippy --all-targets --all-features --locked -- -D warnings
cargo test --all-features --locked
```

### Client Desktop

```bash
cd clients
cargo run
```

### Client Web

```bash
cd clients
dx serve --platform web --port 8081
```

### Client Android

Un émulateur Android doit être démarré au préalable.

```bash
adb devices
adb -s emulator-5554 reverse tcp:8080 tcp:8080
dx serve --platform android --device emulator-5554
```

Les instructions détaillées et les variables de configuration sont disponibles dans le README du client.

## Stockage

Les métadonnées de l'application sont enregistrées dans une base relationnelle SQLite.

Le contenu réel des fichiers est stocké séparément sur un NAS Synology accessible au serveur via NFS.

Les fichiers sont stockés sous forme de blobs utilisant des identifiants techniques opaques. Le nom fourni par l'utilisateur n'est jamais utilisé directement comme chemin physique sur le NAS.

Cette séparation permet de distinguer clairement :

- l'arborescence logique visible par l'utilisateur
- les métadonnées applicatives
- le contenu physique des fichiers

## Fichiers volumineux

Les uploads et téléchargements sont réalisés sous forme de flux.

Lors d'un upload, les données sont traitées progressivement afin de :

- écrire le contenu sur le stockage
- calculer la taille réelle
- calculer l'empreinte SHA-256
- détecter le type MIME
- vérifier le quota
- limiter la consommation mémoire

L'application prend également en charge les requêtes HTTP `Range` pour permettre la lecture partielle d'un fichier.

## Sécurité

Plusieurs mécanismes sont appliqués côté serveur :

- mots de passe hashés avec Argon2id
- authentification avec JWT signés
- contrôle des autorisations côté API
- stockage physique indépendant du nom des fichiers
- quotas utilisateur
- détection du type de contenu
- service Linux exécuté avec un utilisateur dédié
- clés SSH de déploiement dédiées et restreintes
- secrets de CI/CD stockés dans GitHub Secrets
- communication HTTPS via nginx

Les fichiers stockés sur le NAS ne sont jamais exposés directement par le serveur Web. Toute lecture passe par l'API et les contrôles d'autorisation associés.

## Tests et qualité

L'API dispose actuellement de :

- 6 tests unitaires
- 40 tests d'intégration

Le client dispose de :

- 14 tests automatisés

Soit **60 tests automatisés** au total.

Le projet utilise également :

- `cargo fmt`
- Clippy avec les warnings traités comme des erreurs
- des builds indépendants Web, Desktop et Android
- une recette manuelle de l'interface

## CI/CD

L'API et le client Web disposent chacun de leur propre workflow GitHub Actions.

### API

```text
Push
 │
 ▼
cargo fmt
 │
 ▼
Clippy
 │
 ▼
Tests
 │
 ▼
SSH vers le serveur
 │
 ▼
deploy-drive-api
 │
 ▼
cargo build --release
 │
 ▼
redémarrage systemd
```

### Client Web

```text
Push
 │
 ▼
cargo fmt
 │
 ▼
Tests
 │
 ▼
Clippy
 │
 ▼
Build Dioxus Web
 │
 ▼
Archive des fichiers statiques
 │
 ▼
SSH vers le serveur
 │
 ▼
deploy-drive-web
 │
 ▼
/var/www/cave-drive/current
 │
 ▼
nginx
```

Le déploiement n'est réalisé que lorsque les étapes de validation précédentes réussissent.

## Infrastructure

L'environnement actuellement utilisé comprend :

- un serveur Ubuntu pour l'API et le client Web
- nginx comme serveur Web et reverse proxy
- SQLite pour les métadonnées
- un NAS Synology pour le stockage
- NFS entre le serveur et le NAS
- systemd pour l'exécution de l'API
- GitHub Actions pour la CI/CD
- HTTPS pour les échanges exposés

## Limites actuelles

La première version ne prend notamment pas encore en charge :

- les uploads reprenables après interruption
- le versionnement des fichiers
- la déduplication des contenus
- la synchronisation automatique d'un dossier local
- le stockage objet compatible S3
- l'édition collaborative de documents

Les archives de dossiers utilisent actuellement le format TAR.

## Contexte

La Cave Donnarieix a été développée dans le cadre du titre professionnel **Concepteur Développeur d'Applications**.

Le projet a servi de support à différentes problématiques de conception et de développement :

- analyse des besoins
- conception d'une interface multiplateforme
- architecture logicielle
- conception d'une base relationnelle
- développement de composants métier
- sécurité
- gestion de fichiers volumineux
- tests automatisés
- CI/CD
- déploiement sur une infrastructure réelle

## Licence

Projet réalisé dans un contexte personnel et pédagogique.
