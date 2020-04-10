<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
include_file('3rdparty', 'datetimepicker/jquery.datetimepicker', 'css', 'magictrigger');
$plugin = plugin::byId('magictrigger');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
        <div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
            <i class="fas fa-plus-circle" style="font-size : 6em;"></i>
            <br>
            <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">{{Ajouter}}</span>
        </div>
        <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
            <i class="fas fa-wrench" style="font-size : 6em;color:#767676;"></i>
            <br>
            <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
        </div>
        <div class="cursor pluginAction" data-action="openLocation" data-location="<?=$plugin->getDocumentation()?>" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
            <i class="fas fa-book" style="font-size : 6em;color:#767676;"></i>
            <br>
            <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Documentation}}</span>
        </div>
    </div>
    <legend><i class="fas fa-table"></i> {{Mes Logs}}</legend>
    <input class="form-control" placeholder="{{Rechercher}}" style="margin-bottom:4px;" id="in_searchEqlogic" />
    <div class="eqLogicThumbnailContainer">
        <?php
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
            echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
            echo '<img src="' . $eqLogic->getImage() . '" height="105" width="95" />';
            echo "<br>";
            echo '<span class="name" style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<div class="col-xs-12 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
        <span class="input-group-btn">
            <a class="btn btn-primary btn-sm pluginAction roundedLeft" data-action="openLocation" data-location="<?=$plugin->getDocumentation()?>"><i class="fas fa-book"></i> {{Documentation}}</a>
            <a class="btn btn-primary btn-sm bt_showExpressionTest"><i class="fas fa-check"></i> {{Expression}}</a>
            <a class="btn btn-default btn-sm eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
            <a class="btn btn-danger btn-sm eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            <a class="btn btn-success btn-sm eqLogicAction roundedRight" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
        </span>
    </div>
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
        <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
        <li role="presentation"><a href="#monitoringtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-clock"></i> {{Monitoring}}</a></li>
        <li role="presentation"><a href="#infostab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-cog"></i> {{Configuration}}</a></li>
        <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
        <div role="tabpanel" class="tab-pane active" id="eqlogictab">
            <br/>
            <div class="row">
                <div class="col-sm-9">
                    <form class="form-horizontal">
                        <fieldset>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                                <div class="col-sm-3">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                                <div class="col-sm-3">
                                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                        <option value="">{{Aucun}}</option>
                                        <?php
                                            foreach (jeeObject::all() as $object) {
                                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Catégorie}}</label>
                                <div class="col-sm-9">
                                    <?php
                                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                        echo '<label class="checkbox-inline">';
                                        echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                        echo '</label>';
                                        }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label"></label>
                                <div class="col-sm-9">
                                    <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                                    <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                                </div>
                            </div>
                            <br/>

                            <!- benoit5672: add configuration fields ->
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Auto-actualisation (cron)}}</label>
                                <div class="col-sm-2">
                                    <div class="input-group">
                                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Auto-actualisation (cron)}}"/>
                                        <span class="input-group-btn">
                                              <a class="btn btn-default btn-sm " id="bt_cronGenerator" ><i class="fas fa-question-circle"></i></a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label help" data-help="{{Indique le decalage temporel par rapport a l'heure courante. Par exemple, s'il est 15:00 et que le decalage est de 30 minutes, alors on verifie la valeur a 15:30}}">{{Decalage temporel}}</label>
                                <div class="col-sm-2">
                                    <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="timeOffset" min="0" max="120" placeholder="30"/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label help" data-help="{{Indique la periode durant laquelle les informations seront gardees dans jeedom.}}">{{Retention}}</label>
                                <div class="col-sm-2">
                                    <select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="retention">
                                        <option value="0">{{Toujours}}</option>
                                        <option value="3">{{3 mois}}</option>
                                        <option value="6">{{6 mois}}</option>
                                        <option value="12" selected>{{1 an}}</option>
                                        <option value="24">{{2 ans}}</option>
                                        <option value="36">{{3 ans}}</option>
                                    </select>
                                </div>
                            </div>
                            <br/>
		                    <div class="alert alert-warning">
				                {{Remettre a zero la periode d'apprentissage. Toutes les donnees deja collectees seront effacees.}}
							    <a class="pull-right btn btn-warning tooltips" id="bt_razLearning" style="position:relative;top:-7px;" title="Relance le processus d'apprentissage. N'oubliez pas de sauvegarder après la remisea 0."><i class="fas fa-times"></i> RaZ apprentissage</a>
			                </div>
                            <div class="form-group">
                                <label class="col-sm-3 control-label help" data-help="{{le nombre de semaines necessaire pour l'apprentissage. Pendant cette periode, aucune action ne sera declenchee.}}">{{Apprentissage (semaines)}}</label>
                                <div class="col-sm-2">
                                    <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="learning">
                                </div>
                            </div>
			                <div class="form-group">
			                    <label class="col-sm-3 control-label">{{Temps Restant (semaines)}}</label>
				                <div class="col-sm-2">
					                <input type="text" disabled class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="remaining">
				                </div>
			                </div>
                            <!- benoit5672: add configuration fields ->
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
        <!- benoit5672: add configuration fields ->
        <div role="tabpanel" class="tab-pane" id="monitoringtab">
            <div class="row">
                <div class="col-sm-11">
                    <form class="form-horizontal">
                        <fieldset>
                           <br/>
                           <div class="form-horizontal">
                               <legend>{{Periode de monitoring}}</legend>
                               <?php
                                    $days = array ('monday' => '{{Lundi}}', 'tuesday' => '{{Mardi}}', 'wednesday' => '{{Mercredi}}', 
					    'thursday' => '{{Jeudi}}', 'friday' => '{{Vendredi}}', 
					    'saturday' => '{{Samedi}}', 'sunday' => '{{Dimanche}}'); 
                                    foreach ($days as $key => $value) {
                                        echo '<div id ="' . $key . '" class="form-group">';
                                        echo '   <label class="control-label col-sm-2">' . $value . '</label>';
					echo '   <div class="col-sm-1">';
					echo '      <input type="checkbox" class="eqLogicAttr daySelection" data-l1key="configuration" data-l2key="' . $key . '"/>';
					echo '   </div>';
					echo '   <div id="' . $key . 'Full" class="input-control col-sm-2">';
					echo '      <label class="checkbox-inline">';
					echo '      <input type="checkbox" class="eqLogicAttr dayFull" data-l1key="configuration" data-l2key="' . $key . 'Full"/>{{Toute la journee}}';
                                        echo '      </label>';
					echo '   </div>';
                                        echo '   <div id="' . $key . 'Begin" class="input-control">';
                                        echo '      <label class="col-sm-2 control-label col-sm-2">{{Debut}}</label>';
                                        echo '      <input class="col-sm-1 form-control eqLogicAttr input-sm timepicker" data-l1key="configuration" data-l2key="' . $key . 'Begin"/>';
                                        echo '   </div>';
                                        echo '   <div id="' . $key . 'End" class="input-control">';
                                        echo '      <label class="col-sm-2 control-label">{{Fin}}</label>';
                                        echo '      <input class="col-sm-1 form-control eqLogicAttr input-sm timepicker" data-l1key="configuration" data-l2key="' . $key . 'End"/>';
                                        echo '   </div>';
                                        echo '</div>';
				    }
                                ?>
                           </div>
                           <br/>
                           <div class="form-horizontal">
                            <legend>{{Exclusions}}</legend>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{Jours feries}}</label>
                                 <div class="col-sm-1">
                                         <input type="checkbox" class="eqLogicAttr holiday form-control" data-l1key="configuration" data-l2key="holiday"/>
                                 </div>
                                 <div class="col-sm-5">
                                    <div id="holidayInfo" class="input-group">
                                         <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="holidayInfo"/>
                                         <span class="input-group-btn">
                                              <button type="button" class="btn btn-default cursor listCmdInfo tooltips" data-type="holidayInfo" title="{{Rechercher une commande}}"><i class="fas fa-list-alt"></i></button>
                                         </span>
                                     </div>
                                 </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">{{Vacances}}</label>
                                 <div class="col-sm-1">
                                         <input type="checkbox" class="eqLogicAttr vacation form-control" data-l1key="configuration" data-l2key="vacation"/>
                                 </div>
                                 <div class="col-sm-5">
                                    <div id="vacationInfo" class="input-group">
                                         <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="vacationInfo"/>
                                         <span class="input-group-btn">
                                              <button type="button" class="btn btn-default cursor listCmdInfo tooltips" data-type="vacationInfo" title="{{Rechercher une commande}}"><i class="fas fa-list-alt"></i></button>
                                         </span>
                                     </div>
                                 </div>
                            </div>
                            <br/>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" id="infostab">
            <div class="row">
                <div class="col-sm-11">
                    <form class="form-horizontal">
                        <fieldset>
                           <br/>
                           <form class="form-horizontal"> 
			      <fieldset>
			         <legend>
				      {{Declencheurs}}
				     <a class="btn btn-success pull-right addTrigger" data-type="trigger" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter un declencheur}}</a>
			              </legend>
				      <div id="div_trigger">

				      </div>
				  </fieldset>
			    </form>
                            <br/>
                            <div class="form-group">
                            <legend>{{Conditions}}</legend>
                                <label class="col-sm-1 control-label help" data-help="Regroupe les conditions de prise en compte du declencheur. Il n'y a pas besoin de rajouter les conditions liees a la periode de la semaine">{{Condition}}</label>
                                 <div class="col-sm-9">
                                    <div id="condition" class="input-group">
                                         <input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="condition"/>
                                         <span class="input-group-btn">
                                              <button type="button" class="btn btn-default cursor listCmdInfo tooltips" data-type="condition" title="{{Rechercher une commande}}"><i class="fas fa-list-alt"></i></button>
                                         </span>
                                     </div>
                                 </div>
                            </div>
                            <br/>
                            <form class="form-horizontal"> 
			        <fieldset>
			            <legend>
				       {{Actions}}
				       <a class="btn btn-danger pull-right addAction" data-type="action" style="position: relative; top : 5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une action}}</a>
			            </legend>
				    <div id="div_action">

				    </div>
				</fieldset>
			    </form>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
        <!-- benoit5672 configuration -->
	<div role="tabpanel" class="tab-pane" id="commandtab">
	    <a class="btn btn-default btn-sm pull-right" id="bt_addVirtualInfo" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une info virtuelle}}</a>
	    <a class="btn btn-default btn-sm  pull-right" id="bt_addVirtualAction" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande virtuelle}}</a><br/><br/>
	    <table id="table_cmd" class="table table-bordered table-condensed">
		<thead>
		    <tr>
		        <th style="width: 50px;"> ID</th>
			<th style="width: 230px;">{{Nom}}</th>
			<th style="width: 110px;">{{Sous-Type}}</th>
			<th>{{Valeur}}</th>
			<th>{{Paramètres}}</th>
			<th style="width: 300px;">{{Options}}</th>
			<th style="width: 150px;"></th>
		    </tr>
		</thead>
		<tbody>
		
		</tbody>
	     </table>
	</div>
<?php include_file('desktop', 'magictrigger', 'js', 'magictrigger');?>
<?php include_file('core', 'plugin.template', 'js');?>
