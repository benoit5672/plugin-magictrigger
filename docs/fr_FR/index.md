
# Description

Ce plugin permet de collecter des evenements, et de prevoir de maniere 
statistiques les chances que cet evenement se produise pendant une 
periode donnee, et ainsi declencher des actions.

Les evenements sont collectes en fonction des jours de la semaine, 
sur la journee entiere ou seulement une partie de la journee. Il est 
facile de collecter les evenements sur la semaine complete, le week-end,
les jours de travail, le lundi seulement, ...
Il est egalement facile d'exclure la collecte d'evenements les jours feries
et les jours de vacances, ou d'appliquer des filtres complexes a l'aide 
d'une expression jeedom.

Le but etant d'avoir un maximum de "regularite" dans la collecte des evenements, 
pour maximiser la probabilite que l'evenement se produise dans les memes 
conditions.

Les statistiques sont basees sur le nombre d'evenements collectes dans le passe, 
pour le jour courant de la semaine. En fonction des resultats des statistiques, 
on peut declencher differentes actions en utilisant different seuils.

Tous les evenements sont historises en database dans une table dediee au plugin. 
Il y a donc une persistence des evenements en cas de redemarrage de jeedom.

> Une utilisation typique est le declenchement du chauffage en fonction 
> de la premiere personne qui va rentrer a la maison, en fonction du jour d>
> la semaine (lundi-vendredi). Pour cela, on historise tous les changements 
> d'etats des passages de "absent" a "present" au niveau de la maison. Apres 
> une periode d'apprentissage, le plugin va generer une action (un declenchement 
> de scenario par exemple), si la probabilite que quelqu'un rentre entre 16h30 
> et 16h45 depasse 60%.

# Installation

Afin d'utiliser le plugin, vous devez le telecharger, l'installer et 
l'activer comme tout plugin Jeedom.
Il n'y a pas de configuration particuliere a faire sur le plugin.

# Configuration de l'équipement

Le plugin se trouve dans le menu Plugins > Organisation.
Après avoir créé un nouvel equipement, vous devez imperativement remplir
les differentes sections de configuration du plugin.

Il y a trois sections distinctes afin de configurer le plugin:
* Equipement
* Monitoring
* Configuration

## Equipement

La section equipement sert a configurer les parametres habituels de jeedom, 
et egalement les notions de temps du plugin.

![Equipement](/images/equipment.png)

### Periodicite

La periodicite represente la frequence a laquelle le plugin va evaluer 
les statistiques pour l'intervalle souhaite.

### Intervalle

L'intervalle represente l'intervalle de temps utiliser pour les statistiques. 
Il represente le decoupage de la journee en "tranches" utilisees pour les 
statistiques.
Plus l'intervalle est grand, moins il est precis. En effet, cet intervalle 
represente la marge d'erreur.

### Decalage temporel

Le decalage temporel permet de realiser une anticipation dans le temps, 
jusqu'a 120 minutes (2 heures)

### Retention

La periode pendant laquelle sont gardees les informations en base de donnees. 
Le plugin utilise sa propre table en base de donnees afin de conserver les 
informations, et cette table est vraiment optimisee pour utiliser le moins 
de place possible.

Par exemple, avec 2709 evenements collectes et stockes en base de donnees, 
l'espace necessaire n'est que de 0.13MB.

![DB](/images/DB.png)
  
### Periode d'apprentissage

La periode d'apprentissage correspond a la periode pendant laquelle aucune 
action ne sera declenchee. Durant cette periode, uniquement la collecte 
d'evenement sera active. Des que la periode d'apprentissage est terminee, 
le plugin sera en mesure d'executer les actions des que les valeurs seuils 
sont atteintes.La periode d'apprentissage est exprimee en semaines.

## RAZ apprentissage

En cliquant sur ce bouton, toutes les informations contenues pour cet objet 
sont supprimees de la base de donnees.  L'apprentissage reprend au tout debut.

## Exemple de configuration

Dans cet exemple, nous allons verifier si la probabilite que quelqu'un arrive 
a la maison dans les 30 minutes, avec une marge d'erreur de 15 minutes.
Pour cela, nous allons configurer les valeurs suivantes:

**CAS 1**
* periodicite : 5 
* intervalle : 15
* decalage temporel : 30

C'est a dire que toutes les 5 minutes, nous allons verifier la probabilite 
que quelqu'un arrive d'ici 30 a 45 minutes a la maison.

A 15:30, nous allons verifier les statistiques entre 16:00 et 16:15
puis, 5 minutes apres (15:35), nous allons verifier les statistiques entre 
16:05 et 16:20

**CAS 2**
* periodicite : 15 
* intervalle : 15
* decalage temporel : 30

C'est a dire que toutes les 15 minutes, nous allons verifier la probabilite 
que quelqu'un arrive d'ici 30 a 45 minutes a la maison.

A 15:30, nous allons verifier les statistiques entre 16:00 et 16:15
puis, 15 minutes apres (15:45), nous allons verifier les statistiques entre 
16:15 et 16:30

L'action pourrait etre execute plus tard que dans le cas 1. La periodicite 
depend donc de la precision que l'on recherche. 

## Monitoring

La section monitoring sert a configurer les jours qui vont etre surveilles, 
et si besoin d'exclure des periodes (jour ferie ou vacances).

[Monitoring](/images/monitoring.png)

### Periode de monitoring

Dans cette zone, on definit les jours de la semaine a surveiller, ainsi 
que les horaires. Nous pouvons surveiller une journee entiere, une seulement 
une plage horaire de la journee.

### Exclusions

* Jours feries: si les jours feries sont a exclure, alors, il faut indiquer 
une 'info' d'un equipement jeedom qui indique si le jour courant est un jour 
ferie ou non.
* Vacances: si les vacances sont a exclures, alors, il faut indiquer une 
'info' d'un equipement jeedom qui indique si le jour courant est un jour 
de vacances ou non.

Pour ma part, j'utilise le plugin 'information du jour' de lunarok.

## Configuration

Dans la section configuration, on va s'attacher a configurer les evenements a 
surveiller, ainsi que les actions a declencher en fonction de seuils.

[Configuration](/images/configuration.png)

### Declencheurs

Ce sont les evenements a surveiller. Des que la valeur de l'object change, 
alors la condition est evaluee. Si la reponse est positive, alors l'evenement
est stocke en base de donnees. 

### Condition

La condition qui va determiner si l'evenement doit etre pris en compte et etre
utilise par le plugin, ou tout simplement ignore.

La condition peut etre complexe, et utiliser des operateurs logiques (et, ou)
pour combiner differentes evaluations.

Le resultat de l'evaluation doit etre de type 'boolean' (vrai ou faux). Vous
pouvez utiliser le bouton 'Expression' afin de tester le resultat de votre
expression.

Le plugin ajoute a cette condition 'utilisateur' des conditions de temps lies
a la periode de monitoring que vous avez defini dans la section monitoring.

Par exemple, si vous avez cocher 'lundi' de 15:00 a 16:45, alors sera ajouter
```php
(#njour# == 1 && (#time# >= 1500 && #time# <= 1645)
```

### Actions

Les actions sont evaluees en fonction de la 'periodicite' definie dans la 
section 'Equipement'.

Quand la statistique de l'intervalle pour le decalage temporel a ete calculee,
un resultat entre 0% et 100% est generee. C'est la probabilite que l'evenement
se produise a ce moment la.

Pour chaque action, on defini un seuil a partir du quel l'action va etre 
declenchee. Si plusieurs seuils sont definis, on va prendre le seuil le plus
proche de la probabilite calculee, mais en dessous de cette probabilite.

Ainsi, si la probabilite est de 55%, et que les differents seuils sont 
40, 50, 60, alors l'action associe au seuil de 50 sera utilisee.

*Note:*  si plusieurs seuils ont la meme valeur, alors seulement un de ces
seuil sera utilise.

# Le widget

Le widget sera celui par défaut du core avec l'affichage par défaut des commandes
deprendra la configuration de celles-ci.

# Changelog

[Voir le changelog](./changelog)

# Support

Si malgré cette documentation et après voir lu les sujets en rapport avec le plugin sur [community]({{site.forum}}) vous ne trouvez pas de réponse à votre question, n'hésitez pas à créer un nouveau sujet en n'oubliant pas de mettre le tag du plugin ({{site.tagPlugin}}).
