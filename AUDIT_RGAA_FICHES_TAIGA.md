# Audit RGAA GNUT06 — fiches Taiga

Source : `Rapport_Audit_RGAA_GNUT06.pdf`, version 1.0, juillet 2026.

## Organisation proposée

Créer les 8 catégories ci-dessous sous forme d’épiques Taiga. Les fiches sont classées
dans l’ordre recommandé de réalisation.

Priorités :

- **Critique** : correction transversale de niveau A, bloquante pour la navigation ;
- **Haute** : non-conformité de niveau A ou correction transversale ;
- **Normale** : non-conformité de niveau AA ou correction localisée ;
- **Basse** : amélioration hors périmètre de conformité.

Définition de « terminé » commune à toutes les fiches :

- le correctif est vérifié au clavier et avec un lecteur d’écran ;
- aucune régression visuelle n’est constatée sur mobile et ordinateur ;
- les tests automatisés pertinents sont ajoutés ou adaptés ;
- les critères RGAA et pages indiqués dans la fiche ont été contrôlés.

---

## Épique 1 — Gabarit et navigation au clavier

### RGAA-01 — Supprimer la balise `<main>` vide du gabarit

- **Priorité** : Critique
- **Critère** : 9.2 (A)
- **Pages** : les 7 pages auditées
- **Gain estimé** : 7 non-conformités
- **Description** : le gabarit contient deux zones `<main>`, dont une vide. Conserver
  une seule zone de contenu principal par page.
- **Critères d’acceptation** :
  - chaque page rend exactement une balise `<main>` ;
  - cette balise contient le contenu principal ;
  - les repères annoncés par le lecteur d’écran ne contiennent qu’une zone principale.

### RGAA-02 — Identifier l’en-tête principal comme zone `banner`

- **Priorité** : Critique
- **Critère** : 12.6 (A)
- **Pages** : les 7 pages auditées
- **Gain estimé** : 7 non-conformités
- **Description** : rendre l’en-tête principal identifiable par les technologies
  d’assistance.
- **Critères d’acceptation** :
  - l’en-tête principal est un `<header>` rattaché à la page ou porte `role="banner"` ;
  - une seule zone `banner` est exposée par page.

### RGAA-03 — Implémenter un lien d’évitement fonctionnel

- **Priorité** : Critique
- **Critères** : 12.7 (A), 10.7 (A)
- **Pages** : les 7 pages auditées ; liens supplémentaires sur Contact, Connexion et Don
- **Gain estimé** : 10 non-conformités
- **Description** : ajouter un lien permettant d’atteindre directement le contenu
  principal et corriger les liens d’évitement spécifiques existants.
- **Critères d’acceptation** :
  - « Aller au contenu principal » est le premier élément interactif de la page ;
  - le lien devient visible lorsqu’il reçoit le focus ;
  - son activation déplace effectivement le focus au début du contenu principal ;
  - les liens spécifiques vers les formulaires ou sections pointent vers une cible
    existante, deviennent visibles au focus et déplacent correctement celui-ci.

### RGAA-04 — Repositionner le bouton d’aide IA dans l’ordre de tabulation

- **Priorité** : Haute
- **Critère** : 12.8 (A)
- **Pages** : les 7 pages auditées
- **Gain estimé** : 7 non-conformités
- **Description** : le bouton visuellement permanent n’est actuellement atteint
  qu’après tout le contenu.
- **Critères d’acceptation** :
  - le bouton est atteint après « Connexion » et avant le premier lien du contenu ;
  - aucun `tabindex` positif n’est utilisé ;
  - l’ordre de tabulation reste cohérent avec l’ordre visuel.

---

## Épique 2 — Barre de navigation et liens

### RGAA-05 — Simplifier la sémantique de la barre de navigation

- **Priorité** : Haute
- **Critère** : 8.9 (A)
- **Pages** : Accueil, Contact, Connexion, À propos, Don, Évènements
- **Gain estimé** : 6 non-conformités
- **Description** : retirer le modèle ARIA `menubar`, inutile pour cette navigation
  linéaire, et restaurer la sémantique native de liste.
- **Critères d’acceptation** :
  - la navigation utilise `<nav>`, `<ul>`, `<li>` et `<a>` ;
  - les rôles `menubar` et `none` sont supprimés ;
  - tous les liens restent utilisables avec Tab et Entrée.

### RGAA-06 — Aligner le nom accessible des liens de navigation

- **Priorité** : Haute
- **Critère** : 6.1 (A)
- **Pages** : les 7 pages auditées
- **Gain estimé** : 7 non-conformités
- **Description** : supprimer les `aria-label` redondants de la navigation afin que
  le nom accessible corresponde au libellé visible.
- **Critères d’acceptation** :
  - aucun lien de navigation n’a un `aria-label` différent de son libellé visible ;
  - les liens « Notre Mission », « Salle 3D immersive », « Digital Consulting »,
    « Nous soutenir », « Inscription » et « Connexion » sont annoncés selon leur texte.

### RGAA-07 — Rendre explicites les autres liens du site

- **Priorité** : Haute
- **Critère** : 6.1 (A)
- **Pages** : Accueil, Contact, Connexion, À propos, Évènements
- **Description** : corriger les noms accessibles et les libellés signalés dans l’audit.
- **Critères d’acceptation** :
  - Accueil : « Découvrez Gnut06 » n’a plus d’`aria-label` divergent ;
  - les trois « Visitez maintenant » deviennent « Visiter la galerie d’art GNUT »,
    « Accéder à l’application Météo3D GNUT » et « Explorer le projet 3D IMO » ;
  - les ouvertures dans un nouvel onglet sont annoncées visuellement et aux lecteurs
    d’écran, sans faire vocaliser l’icône ;
  - « Découvrez Handi-3D » n’annonce pas une nouvelle fenêtre si elle n’existe pas et
    l’émoji décoratif est masqué aux technologies d’assistance ;
  - Contact : le numéro complet et « Réserver un créneau Visio dès maintenant » sont
    inclus dans leur nom accessible ;
  - Connexion : les liens de mot de passe, Google et Microsoft ont un nom accessible
    identique à leur libellé ;
  - À propos et Évènements : les liens d’adhésion, de découverte et de retour ont un
    libellé visible inclus dans leur nom accessible.

### RGAA-08 — Ajouter une page « Plan du site »

- **Priorité** : Normale
- **Critère** : 12.1 (AA)
- **Pages** : les 7 pages auditées
- **Gain estimé** : 7 non-conformités
- **Description** : fournir un deuxième système de navigation.
- **Critères d’acceptation** :
  - une page « Plan du site » liste les pages et sections principales ;
  - elle est accessible depuis le pied de page de toutes les pages ;
  - ses liens sont à jour, explicites et utilisables au clavier.

---

## Épique 3 — Composants interactifs et scripts

### RGAA-09 — Rendre la modale du chatbot conforme au modèle ARIA Dialog

- **Priorité** : Critique
- **Critère** : 7.1 (A)
- **Pages** : les 7 pages auditées
- **Gain estimé** : 7 non-conformités
- **Critères d’acceptation** :
  - la fenêtre expose `role="dialog"`, `aria-modal="true"` et un nom accessible ;
  - à l’ouverture, le focus est placé dans la modale ;
  - Tab et Maj+Tab restent dans la modale ;
  - Échap ferme la modale ;
  - à la fermeture, le focus revient sur le bouton qui l’a ouverte ;
  - le contenu situé derrière n’est ni atteignable ni manipulable pendant l’ouverture.

### RGAA-10 — Corriger le menu des partenaires du pied de page

- **Priorité** : Haute
- **Critère** : 7.1 (A)
- **Pages** : toutes sauf Mentions légales
- **Description** : remplacer le lien doté de `role="button"` « Vivre avec un
  handicap » par un bouton natif et implémenter un menu accessible.
- **Critères d’acceptation** :
  - le déclencheur est un `<button>` ;
  - son état ouvert/fermé est exposé ;
  - le menu est utilisable au clavier conformément au modèle ARIA Menu Button ;
  - les liens partenaires conservent leur comportement de liens.

### RGAA-11 — Rendre la modale de réservation accessible

- **Priorité** : Haute
- **Critère** : 7.1 (A)
- **Page** : Évènements
- **Critères d’acceptation** : mêmes comportements de focus, fermeture, nom accessible
  et inertie du contenu arrière que dans RGAA-09.

### RGAA-12 — Restaurer le rôle natif des liens

- **Priorité** : Haute
- **Critère** : 7.1 (A)
- **Pages** : Accueil, Connexion, À propos
- **Description** : supprimer `role="button"` des liens qui changent de page.
- **Critères d’acceptation** :
  - les liens de l’accueil, Google/Microsoft et « Rejoignez notre mission » n’ont plus
    `role="button"` ;
  - ils sont annoncés comme liens et activables avec Entrée.

### RGAA-13 — Remplacer le carrousel des évènements par une liste

- **Priorité** : Haute
- **Critères** : 7.1 (A), 9.3 (A)
- **Page** : Évènements
- **Description** : présenter les évènements dans une liste sémantique statique.
- **Critères d’acceptation** :
  - les évènements sont balisés avec `<ul>` et `<li>` ;
  - tous sont disponibles sans action préalable ;
  - aucun comportement ou contrôle de carrousel ne subsiste.

---

## Épique 4 — Structure sémantique

### RGAA-14 — Remplacer les listes ARIA par des listes HTML natives

- **Priorité** : Haute
- **Critère** : 9.3 (A)
- **Pages** : Accueil, À propos, Évènements
- **Gain estimé annoncé par le rapport** : 6 non-conformités pour le lot sémantique
- **Description** : remplacer les `<div role="list">` et leurs éléments par `<ul>` et
  `<li>`.
- **Critères d’acceptation** :
  - Accueil : « Explorez nos Hubs », « Comment vous pouvez nous aider » et « Notre
    équipe tech » sont des listes natives ;
  - À propos : « Chiffres clés » est une liste native ;
  - Évènements : la liste est couverte par RGAA-13 ;
  - la présentation est conservée par CSS.

### RGAA-15 — Corriger le balisage de la section « Chiffres clés »

- **Priorité** : Haute
- **Critères** : 8.9 (A), 9.1 (A)
- **Page** : À propos
- **Description** : les chiffres ne sont pas des titres.
- **Critères d’acceptation** :
  - les `<h3>` de présentation sont remplacés par des paragraphes ou éléments adaptés ;
  - la hiérarchie des titres reste logique ;
  - la mise en forme repose uniquement sur CSS.

### RGAA-16 — Utiliser des paragraphes pour les textes d’aide

- **Priorité** : Haute
- **Critère** : 8.9 (A)
- **Page** : Connexion
- **Critères d’acceptation** :
  - les textes d’aide actuellement placés dans des `<div>` utilisent des `<p>` ;
  - le rendu visuel est conservé par CSS.

---

## Épique 5 — Images

### RGAA-17 — Masquer les images décoratives aux technologies d’assistance

- **Priorité** : Haute
- **Critère** : 1.2 (A)
- **Pages** : Accueil, À propos, Don, Évènements
- **Gain estimé** : 4 non-conformités
- **Description** : vider l’alternative des images décoratives listées dans le rapport :
  introduction, hubs, icônes d’aide, équipe, blocs « Technologies innovantes » et
  « Nos actions », ainsi que toutes les images des pages Don et Évènements.
- **Critères d’acceptation** :
  - chaque image purement décorative possède `alt=""` ;
  - si une alternative est conservée pour une autre finalité, l’image est masquée avec
    `aria-hidden="true"` ;
  - aucune information utile n’est supprimée de la restitution vocale.

### RGAA-18 — Corriger les alternatives des images informatives

- **Priorité** : Haute
- **Critère** : 1.3 (A)
- **Page** : Accueil
- **Critères d’acceptation** :
  - l’image 1 a pour alternative « Personne en fauteuil roulant portant un casque de
    réalité virtuelle » ;
  - l’image 2 a pour alternative « Membre de l’association GNUT06 plaçant un casque
    de réalité virtuelle pour une personne en fauteuil roulant ».

---

## Épique 6 — Couleurs et contrastes

### RGAA-19 — Corriger le contraste du formulaire de contact

- **Priorité** : Normale
- **Critère** : 3.2 (AA)
- **Page** : Contact
- **Critères d’acceptation** :
  - l’astérisque des champs obligatoires est blanc ou atteint un contraste de 4,5:1 ;
  - les messages d’erreur atteignent au moins 4,5:1 sur le fond noir ;
  - les états normal, survol, focus et désactivé restent lisibles.

### RGAA-20 — Corriger les contrastes du bouton « Se connecter »

- **Priorité** : Normale
- **Critères** : 3.2 (AA), 3.3 (AA)
- **Page** : Connexion
- **Critères d’acceptation** :
  - le texte atteint au moins 4,5:1 avec le fond du bouton ;
  - le contour ou fond du bouton atteint au moins 3:1 avec son environnement ;
  - les états survol, focus et désactivé respectent également ces seuils.

### RGAA-21 — Rendre les contrôles du carrousel perceptibles sans la couleur

- **Priorité** : Haute
- **Critères** : 3.1 (A), 3.3 (AA)
- **Page** : Accueil
- **Critères d’acceptation** :
  - l’état actif des pastilles possède un indicateur autre que la couleur, par exemple
    une taille ou une forme distincte ;
  - les pastilles inactives et flèches atteignent un contraste d’au moins 3:1 ;
  - tous les contrôles restent visibles au focus.

---

## Épique 7 — Formulaires et présentation

### RGAA-22 — Corriger le groupe de boutons radio « Type de projet »

- **Priorité** : Haute
- **Critère** : 11.5 (A)
- **Page** : Contact
- **Critères d’acceptation** :
  - un seul mécanisme regroupe les boutons radio ;
  - si `role="radiogroup"` est conservé, il possède `aria-labelledby` vers « Type de
    projet » et le `<fieldset>` redondant est supprimé ;
  - le nom du groupe et l’option sélectionnée sont correctement annoncés.

### RGAA-23 — Signaler les champs obligatoires du formulaire de connexion

- **Priorité** : Haute
- **Critère** : 11.10 (A)
- **Page** : Connexion
- **Critères d’acceptation** :
  - la mention « Toutes les informations sont obligatoires » précède les champs ;
  - cette information est disponible visuellement et aux lecteurs d’écran ;
  - les erreurs de saisie restent identifiées et associées aux champs concernés.

### RGAA-24 — Retirer les attributs de présentation des iframes

- **Priorité** : Haute
- **Critère** : 10.1 (A)
- **Page** : Contact
- **Critères d’acceptation** :
  - Google Maps n’utilise plus les attributs HTML `width` et `height` pour sa mise en
    forme ;
  - le captcha n’utilise plus `width`, `height` ni `frameborder` à cette fin ;
  - dimensions et bordures sont gérées par CSS, avec un rendu responsive.

### RGAA-25 — Placer la mention de nouvelle fenêtre dans le HTML

- **Priorité** : Haute
- **Critère** : 10.2 (A)
- **Page** : Contact
- **Critères d’acceptation** :
  - « Réserver un créneau Visio dès maintenant » est suivi dans le DOM d’une mention
    « (nouvelle fenêtre) » ;
  - l’information reste disponible lorsque CSS est désactivé ;
  - elle n’est pas dupliquée par un pseudo-élément CSS.

---

## Épique 8 — Contenus en mouvement et dette qualité

### RGAA-26 — Supprimer le défilement automatique des partenaires

- **Priorité** : Haute
- **Critère** : 13.8 (A)
- **Page** : Accueil
- **Description** : privilégier une liste statique des partenaires sur plusieurs lignes.
- **Critères d’acceptation** :
  - aucun défilement automatique ne démarre ;
  - tous les partenaires sont visibles et accessibles au clavier ;
  - si l’animation est conservée, des commandes accessibles permettent de la mettre
    en pause et de la relancer.

### RGAA-27 — Harmoniser les libellés des appels à l’action

- **Priorité** : Basse
- **Type** : amélioration hors périmètre RGAA
- **Pages** : selon occurrence
- **Critères d’acceptation** :
  - « Je donne » devient « Faire un don » ;
  - « J’adhère » devient « Devenir adhérent de l’association » ;
  - « Mot de passe oublié ? » devient « Réinitialiser votre mot de passe » ;
  - les changements restent compatibles avec les exigences de RGAA-07.

---

## Suivi séparé — Dérogation du formulaire de don

### RGAA-28 — Obtenir l’état d’accessibilité du prestataire de don

- **Priorité** : Haute
- **Critères concernés** : 11.1 à 11.13
- **Page** : Don
- **Type** : action fournisseur, pas une correction de conformité interne
- **Description** : l’iframe de don n’est pas maîtrisée par GNUT06. Demander au
  prestataire sa déclaration ou son audit d’accessibilité et un plan de correction.
- **Critères d’acceptation** :
  - une réponse documentée du prestataire est attachée à la fiche ;
  - le motif manquant de la dérogation 11.3 est clarifié ;
  - les défauts connus, délais et engagements sont consignés.

### RGAA-29 — Proposer un moyen alternatif accessible pour faire un don

- **Priorité** : Haute
- **Critères concernés** : 11.1 à 11.13
- **Page** : Don
- **Description** : prévoir une solution si l’iframe empêche une personne handicapée
  d’effectuer un don.
- **Critères d’acceptation** :
  - une méthode alternative est clairement signalée avant ou à proximité de l’iframe ;
  - elle permet réellement d’effectuer un don sans utiliser le composant tiers ;
  - son parcours complet est conforme et testé au clavier et avec un lecteur d’écran.

---

## Ordre de livraison conseillé

1. RGAA-01 à RGAA-06 et RGAA-09 : gabarit et composants transversaux ;
2. RGAA-08, RGAA-10 à RGAA-16 : navigation, scripts et structure ;
3. RGAA-17 à RGAA-21 : images et contrastes ;
4. RGAA-22 à RGAA-26 : formulaires et corrections localisées ;
5. RGAA-27 à RGAA-29 : dette qualité et suivi de la dérogation.

Les gains indiqués ne doivent pas être additionnés mécaniquement : certains correctifs
répondent simultanément à plusieurs critères ou occurrences.
