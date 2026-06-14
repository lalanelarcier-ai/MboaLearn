# MboaLearn - TP INF222

Mini Learning Management System realise dans le cadre du TP de l'UE INF222 - Programmation web.

## Deploiement Docker (local)

```bash
docker compose up -d --build
```

Ouvrir : http://localhost:8080/MboaLearn/

## Deploiement sur Render

1. Pousser le code sur GitHub
2. Creer un compte sur [render.com](https://render.com)
3. New > Web Service > Connexion a GitHub
4. Choisir le repo MboaLearn
5. Render detecte le `render.yaml` automatiquement
6. La base de donnees MySQL est creee automatiquement
7. Attendre le premier deploiement

## Comptes de test

| Role | Email | Mot de passe |
|------|-------|-------------|
| Promoteur | admin@lms.com | password |
| Enseignant | dupont@lms.com | password |
| Etudiant | jean@lms.com | password |

## Arreter Docker

```bash
docker compose down
```
