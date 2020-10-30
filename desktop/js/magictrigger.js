/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */


/********************************************************************************/

/**
 * Presentation et recuperation des donnees
 */
function saveEqLogic(_eqLogic) {
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    _eqLogic.configuration.triggers = $('#div_trigger .trigger').getValues('.expressionAttr');
    _eqLogic.configuration.actions  = $('#div_action .action').getValues('.expressionAttr');
    //console.log('triggers');
    //console.dir(_eqLogic.configuration.triggers);
    //console.log('actions');
    //console.dir(_eqLogic.configuration.actions);
    return _eqLogic;
}

function printEqLogic(_eqLogic) {
    actionOptions = [];
    $('#div_trigger').empty();
    $('#div_action').empty();
    if (isset(_eqLogic.configuration)) {
        if (isset(_eqLogic.configuration.triggers)) {
            for (var i in _eqLogic.configuration.triggers) {
                addTrigger(_eqLogic.configuration.triggers[i], 'trigger');
	        }
	    }
        if (isset(_eqLogic.configuration.actions)) {
            for (var i in _eqLogic.configuration.actions) {
                addAction(_eqLogic.configuration.actions[i], 'action');
	        }
	    }
    }
    jeedom.cmd.displayActionsOption({
        params : actionOptions,
        async : false,
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success : function(data){
            for(var i in data){
                $('#'+data[i].id).append(data[i].html.html);
            }
            taAutosize();
        }
    });

}


/********************************************************************************/

/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */
function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    tr += '<div class="row">';
    tr += '<div class="col-sm-6">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '<div class="col-sm-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});


/********************************************************************************/
/*
 * table des actions
 * function addAction
 * function removeAction
 */
$('#div_action').sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.addAction').off('click').on('click', function () {
    addAction({}, $(this).attr('data-type'));
});

$('body').off('click', '.listCmdAction').on('click', '.listCmdAction', function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
      taAutosize();
    });
  });
});

$('body').off('click','.listAction').on('click','.listAction',  function () {
  var type = $(this).attr('data-type');
  var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
  jeedom.getSelectActionModal({}, function (result) {
    el.value(result.human);
    jeedom.cmd.displayActionOption(el.value(), '', function (html) {
      el.closest('.' + type).find('.actionOptions').html(html);
      taAutosize();
    });
  });
});

$('body').off('focusout','.cmdAction.expressionAttr[data-l1key=cmd]').on('focusout','.cmdAction.expressionAttr[data-l1key=cmd]',function (event) {
    var type = $(this).attr('data-type');
    var expression = $(this).closest('.' + type).getValues('.expressionAttr');
    var el = $(this);
    jeedom.cmd.displayActionOption($(this).value(), init(expression[0].options), function (html) {
	      el.closest('.' + type).find('.actionOptions').html(html);
    });
});

$('body').off('click','.bt_removeAction').on('click','.bt_removeAction',function () {
   var type = $(this).attr('data-type');
   $(this).closest('.' + type).remove();
});


function addAction(_action, _type) {
    if (!isset(_action.options)) {
        _action.options = {};
    }
    var div = '<div class="' + _type + '">';
    div += '<div class="form-group">';
    div += '<label class="col-sm-1 control-label">{{Seuil}}</label>';
    div += '<div class="col-sm-1">';
    div += '<input class="expressionAttr form-control" data-l1key="threshold">';
    div += '</div>';
    div += '<label class="col-sm-1 control-label">Action</label>';
    div += '<div class="col-sm-3">';
    div += '<div class="input-group">';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default bt_removeAction roundedLeft" data-type="' + _type + '"><i class="fas fa-minus-circle"></i></a>';
    div += '</span>';
    div += '<input class="expressionAttr form-control input-sm cmdAction" data-l1key="cmd" data-type="' + _type + '" />';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default btn-sm listAction" data-type="' + _type + '" title="{{Sélectionner un mot-clé}}"><i class="fa fa-tasks"></i></a>';
    div += '<a class="btn btn-default btn-sm listCmdAction roundedRight" data-type="' + _type + '"><i class="fas fa-list-alt"></i></a>';
    div += '</span>';
    div += '</div>';
    div += '</div>';
    var actionOption_id = uniqId();
    div += '<div class="col-sm-5 actionOptions" id="'+ actionOption_id +'">';
    div += '</div>';
    div += '</div>';
    $('#div_' + _type).append(div);
    $('#div_' + _type + ' .' + _type + '').last().setValues(_action, '.expressionAttr');
    actionOptions.push({
        expression : init(_action.cmd, ''),
        options : _action.options,
       id : actionOption_id
    });
}


/********************************************************************************/
/*
 * table des triggers
 * function addTrigger
 * function removeTrigger
 */
$('#div_trigger').sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.addTrigger').off('click').on('click', function () {
    addTrigger({}, $(this).attr('data-type'));
});


$('body').off('click','.listCmdTrigger').on('click','.listCmdTrigger', function () {
    var type = $(this).attr('data-type');
	var el = $(this).closest('.' + type).find('.expressionAttr[data-l1key=cmd]');
    //jeedom.cmd.getSelectModal({cmd: {type: 'info', subType: 'binary'}}, function (result) {
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
        el.value(result.human);
    });
});


$('body').off('focusout','.cmdTrigger.expressionAttr[data-l1key=cmd]').on('focusout','.cmdTrigger.expressionAttr[data-l1key=cmd]',function (event) {
    var type = $(this).attr('data-type');
    var expression = $(this).closest('.' + type).getValues('.expressionAttr');
    var el = $(this);
});

$('body').off('click','.bt_removeInfo').on('click','.bt_removeInfo',function () {
   var type = $(this).attr('data-type');
   $(this).closest('.' + type).remove();
});


function addTrigger(trigger, _type) {
    var div = '<div class="' + _type + '">';
    div += '<div class="form-group ">';
    div += '<label class="col-sm-1 control-label">{{Declencheur}}</label>';
    div += '<div class="col-sm-4">';
    div += '<div class="input-group">';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default bt_removeInfo roundedLeft" data-type="' + _type + '"><i class="fas fa-minus-circle"></i></a>';
    div += '</span>';
    div += '<input class="expressionAttr form-control cmdTrigger" data-l1key="cmd" data-type="' + _type + '" />';
    div += '<span class="input-group-btn">';
    div += '<a class="btn btn-default listCmdTrigger roundedRight" data-type="' + _type + '"><i class="fas fa-list-alt"></i></a>';
    div += '</span>';
    div += '</div>';
    div += '</div>';
    div += '</div>';
    $('#div_' + _type).append(div);
    $('#div_' + _type + ' .' + _type + '').last().setValues(trigger, '.expressionAttr');
}

/*********************************************************************************************/
/**
 * Gestion des elements de configuration
 * Jours feries
 * Vacances
 * condition
 */
$('body').off('click','.listCmdInfo').on('click','.listCmdInfo', function () {
    var type = $(this).attr('data-type');
	var el = $(this).closest('#' + type).find('.eqLogicAttr[data-l1key=configuration][data-l2key=' + type + ']');
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
        el.value(result.human);
    });
});

/*********************************************************************************************/
/** 
 * Periodes : gestion datetimepicker et show/hide pour la valeur debut/fin en fonction de 
 * "toute la journee"
 */
$('.timepicker').datetimepicker({
    lang: 'fr',
    datepicker: false,
    format: 'H:i',
    step: 15
});

/* fonction appelee apres le chargement du tab de monitoring, mais avant affichage */
$("a[href='#monitoringtab']").on('show.bs.tab', function(e) {
     
    // Gestion des jours, avec affichage ou non des champs "toute la journee" "debut" et "fin"
    var days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
    days.forEach(function(item, index, array) {
        var root = $('#' + item);
        var day  = root.attr('id');
        if (root.find('.eqLogicAttr[data-l1key=configuration][data-l2key=' + day + ']').value() == 1) {
	    full = root.find('#' + day + 'Full');
            full.show();
	    if (full.find('.eqLogicAttr[data-l1key=configuration][data-l2key=' + day + 'Full]').value() == 0) {
                root.find('#' + day + 'Start').show();
                root.find('#' + day + 'End').show();
            } else {
                root.find('#' + day + 'Start').hide();
                root.find('#' + day + 'End').hide();
	    }
        } else {
            root.find('#' + day + 'Full').hide();
            root.find('#' + day + 'Start').hide();
            root.find('#' + day + 'End').hide();
        }
    });

    // Gestion des exclusions
    // par defaut, les jours feries et les vacances scolaires sont pris en compte
    var root = $('#monitoringtab');
    if (root.find('.eqLogicAttr[data-l1key=configuration][data-l2key=holiday]').value() == 1) {
        root.find('#holidayInfo').show(); 
    } else {
        root.find('#holidayInfo').hide(); 
    }
    if (root.find('.eqLogicAttr[data-l1key=configuration][data-l2key=vacation]').value() == 1) {
        root.find('#vacationInfo').show(); 
    } else {
        root.find('#vacationInfo').hide(); 
    }
});

$('body').off('click','.daySelection').on('click','.daySelection',function (event) {
    
    var day = $(this).attr('data-l2key');
    var root = $(this).closest('#' + day);
    if ($(this).value() == 1){
	full = root.find('#' + day + 'Full');
        full.show();
	if (full.find('.eqLogicAttr[data-l1key=configuration][data-l2key=' + day + 'Full]').value() == 0) {
            root.find('#' + day + 'Start').show();
            root.find('#' + day + 'End').show();
        } else {
            root.find('#' + day + 'Start').hide();
            root.find('#' + day + 'End').hide();
	}
    } else {
        root.find('#' + day + 'Full').hide();
        root.find('#' + day + 'Start').hide();
        root.find('#' + day + 'End').hide();
    }
});


$('body').off('click','.dayFull').on('click','.dayFull',function (event) {
    
    var root = $(this).closest('.form-group');
    var day = root.attr('id');
    if ($(this).value() == 1){
        root.find('#' + day + 'Start').hide();
        root.find('#' + day + 'End').hide();
    } else {
        root.find('#' + day + 'Start').show();
        root.find('#' + day + 'End').show();
    }
});


$('body').off('click','.holiday').on('click','.holiday',function (event) {
    var root = $('#monitoringtab');
    if (root.find('.eqLogicAttr[data-l1key=configuration][data-l2key=holiday]').value() == 1) {
        root.find('#holidayInfo').show(); 
    } else {
        root.find('#holidayInfo').hide(); 
    }
});


$('body').off('click','.vacation').on('click','.vacation',function (event) {
    var root = $('#monitoringtab');
    if (root.find('.eqLogicAttr[data-l1key=configuration][data-l2key=vacation]').value() == 1) {
        root.find('#vacationInfo').show(); 
    } else {
        root.find('#vacationInfo').hide(); 
    }
});


/*********************************************************************************************/
$('.bt_showExpressionTest').off('click').on('click', function () {
    $('#md_modal').dialog({title: "{{Testeur d'expression}}"});
    $("#md_modal").load('index.php?v=d&modal=expression.test').dialog('open');
});


/*********************************************************************************************/
// Learning section
//
function setRemaining() {

    var root = $('#learning');
	var startDate = root.find('.eqLogicAttr[data-l1key=configuration][data-l2key="learningStartDate"]');
	var remaining = root.find('.eqLogicAttr[data-l1key=configuration][data-l2key="remaining"]');
	var learning  = root.find('.eqLogicAttr[data-l1key=configuration][data-l2key="learning"]');

    var end     = parseInt(startDate.value(), 10) + (parseInt(learning.value(), 10) * 604800);
    var current = Math.round(new Date().getTime()/1000);

    var seconds = (end - current);
    var minutes = Math.floor(seconds/60);
    var hours = Math.floor(minutes/60);
    var days = Math.floor(hours/24);
    var weeks = Math.floor(days/7);
    days    = days-(weeks*7); 
    hours   = hours-(weeks*24*7)-(days*24);
    minutes = minutes-(weeks*24*7*24*60)-(days*24*60)-(hours*60);

    value = "";
    if (weeks > 0) {
        value = value.concat(weeks, 's');
    }
    if (value.length > 0 && days > 0) {
        value = value.concat(', ', days, 'j');
    }
    if (value.length > 0 && hours > 0) {
        value = value.concat(', ', hours, 'h');
    }
    if (value.length > 0 && minutes > 0) {
        value = value.concat(', ', minutes, 'm');
    }
    remaining.val(value);
}

$('body').off('click','.bt_razLearning').on('click','.bt_razLearning', function () {

    bootbox.confirm('{{Etes-vous sur de vouloir supprimer l\'historique des donnees et recommencer l\'apprentissage}}', function(result) {
        if (result) {
            var root      = $('#learning');
	        var startDate = root.find('.eqLogicAttr[data-l1key=configuration][data-l2key="learningStartDate"]');
            var date      = Math.round(new Date().getTime()/1000);
            startDate.val(date);
            // Update remaining value
            setRemaining();

            // Remove the entries from the database
	        $.ajax({
	            type: "POST", 
		        url: "plugins/magictrigger/core/ajax/magictrigger.ajax.php",
		        data: {
		         action: "removeAllbyId",
                    id: $('.eqLogicAttr[data-l1key=id]').value(),
		        },
		        dataType: 'json',
		        global: true,
		        async: false,
		        error: function (request, status, error) {
		         handleAjaxError(request, status, error);
		        },
		        success: function (data) {
		        if (data.state != 'ok') {
		            $('#div_alert').showAlert({message: data.result, level: 'danger'});
		         return;
		        }
		        list = data['result'];
		        }
	        });
        }
    });
});

$("a[href='#eqlogictab']").on('show.bs.tab', function(e) {

    // When the tab is displayed, refresh the remaining value
    setRemaining();
});

# ---- Thanks @mips: open documentation or community links from the desktop
$('.pluginAction[data-action=openLocation]').on('click',function(){
    window.open($(this).attr("data-location"), "_blank", null);
});
