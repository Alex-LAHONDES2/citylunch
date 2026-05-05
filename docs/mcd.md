# Modèle conceptuel de données (MCD)

## Entités

### Produit (`product`)
- `id` (PK)
- `name`
- `description` (nullable)
- `price`
- `image` (nullable)

### Livreur (`user`)
Dans cette version, un livreur est un `User` ayant le rôle `ROLE_COURIER`.
- `id` (PK)
- `email` (unique)
- `password` (hash)
- `roles` (JSON)

### Sac (`Bag`, table `bag`)
Le sac représente le contenu chargé par un livreur.
- `id` (PK)
- `deliverer_id` (FK → `user.id`, unique)

### Contenu de sac (`BagItem`, table `bag_item`)
- `id` (PK)
- `bag_id` (FK → `bag.id`)
- `product_id` (FK → `product.id`)
- `quantity` (int > 0)

## Relations
- **Livreur (1,1)** — possède — **Sac (1,1)**
- **Sac (1,n)** — contient — **Contenu de sac (0,n)**
- **Contenu de sac (n,1)** — référence — **Produit (1,1)**

## Contraintes métier (implémentées)
- Un livreur ne peut pas retirer d’un sac une quantité supérieure à celle présente.
- Les quantités dans un sac doivent être strictement positives.
