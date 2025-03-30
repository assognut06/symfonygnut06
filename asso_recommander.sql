-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : mysql:3306
-- Généré le : dim. 23 juin 2024 à 17:31
-- Version du serveur : 5.7.44
-- Version de PHP : 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gnut06`
--

-- --------------------------------------------------------

--
-- Structure de la table `asso_recommander`
--

CREATE TABLE `asso_recommander` (
  `id` int(11) NOT NULL,
  `organization_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `banner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fiscal_receipt_eligibility` tinyint(1) DEFAULT NULL,
  `fiscal_receipt_issuance_enabled` tinyint(1) DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `asso_recommander`
--

INSERT INTO `asso_recommander` (`id`, `organization_slug`, `banner`, `fiscal_receipt_eligibility`, `fiscal_receipt_issuance_enabled`, `type`, `category`, `logo`, `name`, `city`, `zip_code`, `description`, `url`, `created_at`, `updated_at`) VALUES
(1, 'le-chemin-des-reves', 'https://cdn.helloasso.com/img/photos/croppedimage-fe3bc63af17d4c7cba4f7e3ef1d4d73e.png', 1, 0, 'Association1901Rig', 'Action sociale', 'https://cdn.helloasso.com/img/logos/le chemin des reves-763967b313f7448b9d7d1012366613f9.jpg', 'Le Chemin Des Rêves', 'Nice', '06300', 'Parce que le handicap n\'est pas une différence, ensemble changeons les mentalités !!!', 'https://www.helloasso.com/associations/le-chemin-des-reves', NULL, NULL),
(3, 'tous-en-piste-avec-baptiste', 'https://cdn.helloasso.com/img/photos/croppedimage-373f207f93a94d09b3ecdbe3f1a392aa.jpg', 0, 0, 'Association1901', 'Médico-sociale', 'https://cdn.helloasso.com/img/logos/croppedimage-d732ac6967eb403eb98f8e6c3267835d.png', 'Tous en piste avec Baptiste ', 'Nice', '06000', 'Cette association a été créée pour collecter des fonds afin que, Baptiste, polyhandicapé atteint d\'une mutation génétique rare, puisse bénéficier d\'une prise en charge médicale la plus importante possible (stage de rééducation à l\'étranger, matériel). Il s\'agit aussi de sensibiliser au handicap.', 'https://www.helloasso.com/associations/tous-en-piste-avec-baptiste', NULL, NULL),
(4, 'accompagnement-et-repit-des-aidants', 'https://cdn.helloasso.com/img/photos/croppedimage-84f0b69fb7fc478e831153dd272246cb.png', 1, 0, 'Association1901Rig', 'Action sociale', 'https://cdn.helloasso.com/img/logos/croppedimage-907acf65945d4311925e69b266c12623.png', 'Accompagnement et Répit des Aidants', 'Nice', '06300', 'L’association ARA est dédiée à toutes les personnes qui aident régulièrement un proche fragilisé par la maladie, le handicap, la perte d’autonomie.\nElle accompagne tous les aidants sans distinction d’âge, de pathologie, de type d’aide et de situation, tout au long de la relation d’aide.', 'https://www.helloasso.com/associations/accompagnement-et-repit-des-aidants', NULL, NULL),
(5, 'smile4all', 'https://cdn.helloasso.com/img/photos/croppedimage-e4c98fe252294674a9aae696f317791a.png', 0, 0, 'Association1901', 'Action sociale', 'https://cdn.helloasso.com/img/logos/croppedimage-6c2f4cd9dc8f4a83bee72cc574b8f4d6.png', 'Smile4All', 'Nice', '06200', 'Notre association a pour but d’apporter du bien-être aux personnes isolées de la société tel que les enfants malades, en situation de handicap ou encore les personnes âgées', 'https://www.helloasso.com/associations/smile4all', NULL, NULL),
(6, 'handicap-aventure', 'https://cdn.helloasso.com/img/photos/croppedimage-d98354ecd919419e8b2ab5d68f4c5edd.png', 0, 0, 'Association1901', 'Sports', 'https://cdn.helloasso.com/img/logos/croppedimage-3db31e39ab0e4ea5b2d6f366455b7b43.png', 'Handicap Aventure', 'Nice', '06000', 'Adaptation des sports de montagne et des matériaux de montagne et spéléologie pour le monde du handicap et grand handicap aux fins de l\'accessibilité pour tous. \nAssociation existante depuis le 8 Juin 1990', 'https://www.helloasso.com/associations/handicap-aventure', NULL, NULL),
(7, 'france-alzheimer-alpes-maritimes', 'https://cdn.helloasso.com/img/photos/croppedimage-b2a2e9b87fe24486a105e8441e8ecacb.png', 1, 1, 'Association1901Rig', 'Médico-sociale', 'https://cdn.helloasso.com/img/logos/06_alpes-maritimes.jpg', 'France alzheimer Alpes-Maritimes', 'Nice', '6100', 'L\'association France Alzheimer 06 accompagne, soutient, informe, forme et oriente les personnes en situation de handicap cognitifs ainsi que leurs proches accompagnants dans le département des Alpes-Maritimes. elle crée des dispositifs de répit (accueil de jour, plate forme d\'accompagnement)', 'https://www.helloasso.com/associations/france-alzheimer-alpes-maritimes', NULL, NULL),
(8, 'anices', 'https://cdn.helloasso.com/img/photos/croppedimage-f853afdc37604bf29096ca6dee3e955f.png', 0, 0, 'Association1901', 'Sports', 'https://cdn.helloasso.com/img/logos/croppedimage-34c7c6f249ef48018b690b8a5efc4973.png', 'ANICES', 'Nice', '06100', 'ANICES pour Association Niçoise d\'Initiatives Culturelles Et Sportives est née en 2007 à Nice dans le but d\'offrir aux personnes en situation de handicaps des activités sportives et culturelles !', 'https://www.helloasso.com/associations/anices', NULL, NULL),
(9, 'handssemble', 'https://cdn.helloasso.com/img/photos/croppedimage-35c5daf1edd449e9bef9a8c2ccbb0958.png', 0, 0, 'Association1901', 'Action sociale', 'https://cdn.helloasso.com/img/logos/croppedimage-9c229171b9e7452f9e36511dff5fd3c4.png', 'HandsSemble', 'Nice', '06000', 'Notre association est une association qui a pour projet d’organiser différents évènements visant à sensibiliser différents publics (jeunes et adultes) sur les situations de handicap. \n\n', 'https://www.helloasso.com/associations/handssemble', NULL, NULL),
(10, 'ma-place-a-moi', 'https://cdn.helloasso.com/img/photos/croppedimage-8a2085da92d747e78421ead5f5df86cb.png', 1, 1, 'Association1901Rig', 'Médico-sociale', 'https://cdn.helloasso.com/img/logos/croppedimage-18120aea8c864863b56f6cbf8d076abd.png', 'Ma Place à Moi', 'Nice', '06000', 'Défendre la création d\'un service spécialisé d\'aide à domicile.\nÉcouter, aider, accompagner les parents et leurs proches en situation de handicap. \nPermettre aux parents d\'échanger et aux enfants adolescents et adultes de se rencontrer. \nSensibiliser tout public sur les questions liées au handicap', 'https://www.helloasso.com/associations/ma-place-a-moi', NULL, NULL),
(11, 'comite-departementale-sport-adapte-des-alpes-marit', 'https://cdn.helloasso.com/img/photos/croppedimage-766d5d01eb5f4cbebc1c766e36fb7759.png', 0, 0, 'Association1901', 'Sports', 'https://cdn.helloasso.com/img/logos/croppedimage-e682910179984151881b580e25b78e3f.png', 'Comité Départementale Sport Adapté des Alpes-Maritimes', 'Nice', '06200', 'Coordonne les projets d\'activités physiques adaptées à destination des personnes en situation de handicap mental et/ou atteintes de troubles psychiques.', 'https://www.helloasso.com/associations/comite-departementale-sport-adapte-des-alpes-marit', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `asso_recommander`
--
ALTER TABLE `asso_recommander`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `asso_recommander`
--
ALTER TABLE `asso_recommander`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
