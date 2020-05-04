# jeedom-magictrigger
Plugin permettant de collecter des evenements, et de prevoir de maniere statistiques les chances que cet evenement se produise  pour declenchers des actions.

Le nombre d'evenements est totalise par jour de la semaine, avec potentiellement des conditions complexes, et permet donc de faire des statistics difficilement realisable via scenario et historique.

Une utilisation typique est le declenchement du chauffage en fonction de la premiere personne qui va rentrer a la maison, en fonction du jour de la semaine (lundi-vendredi). Pour cela, on historise tous les changements d'etats des passages de "absent" a "present" au niveau de la maison. Apres une periode d'apprentissage, le plugin va generer une action (un declenchement de scenario par exemple), si la probabilite que quelqu'un rentre entre 16h30 et 16h45 depasse 80%.
