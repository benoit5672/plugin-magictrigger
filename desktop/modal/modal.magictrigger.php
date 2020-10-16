<?php

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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>

<div id="magictrigger-modal" class="row row-overflow">
    <div class="col-sm-10" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <form class="form-horizontal">
            <fieldset>
                <div class="form-group">
                    <label class="col-sm-1 control-label">{{Jour}}</label>
                    <div class="col-sm-2">
						<select class="form-control" id="dow">
							<option value="-1" selected="selected">{{Courant}}</option>
							<option value="0">{{Dimanche}}</option>
							<option value="1">{{Lundi}}</option>
							<option value="2">{{Mardi}}</option>
							<option value="3">{{Mercredi}}</option>
							<option value="4">{{Jeudi}}</option>
							<option value="5">{{Vendredi}}</option>
							<option value="6">{{Samedi}}</option>
						</select>
                    </div>
                    <label class="col-sm-1 control-label">{{Periodicite}}</label>
                    <div class="col-sm-2">
						<select class="form-control" id="period">
							<option value="0" selected="selected">{{configure}}</option>
							<option value="5">{{5 minutes}}</option>
							<option value="10">{{10 minutes}}</option>
							<option value="15">{{15 minutes}}</option>
							<option value="30">{{30 minutes}}</option>
							<option value="60">{{1 heure}}</option>
						</select>
                    </div>
                    <label class="col-sm-1 control-label">{{Intervalle}}</label>
                    <div class="col-sm-2">
						<select class="form-control" id="interval">
							<option value="0" selected="selected">{{configure}}</option>
							<option value="5">{{5 minutes}}</option>
							<option value="10">{{10 minutes}}</option>
							<option value="15">{{15 minutes}}</option>
							<option value="30">{{30 minutes}}</option>
							<option value="60">{{1 heure}}</option>
						</select>
                    </div>
                    <div class="col-sm-1">
	                    <button id="btn_modal_show" type="button" class="btn btn-default pull-right">{{Voir graphe}}</button>
                    </div>
                </div>
            </fieldset>
		</form>
	</div>
    <center><div id="div_message"></div></center>
    <center><div id="div_graph"></div></center>
</div>

<div class="modal-footer">
	<button id="btn_modal_close" type="button" class="btn btn-default">{{Fermer}}</button>
</div>

<script>

var cmd_id = <?php echo $_GET['cmd_id']; ?>;

function removeMessage() {
    $('#div_message').find('center').remove();
}

function drawGraph(_dow, _period, _interval) {

    //console.log('Entering drawGraph: ' + _cmd_id);

    // Initialize values
    var categories = [];
    var series     = [];
    var min_threshold = 0;
    var max_threshold = 0;
    var period        = -1;
    var dow           = -1;
    var interval      = -1;

    // Fetch the data using AJAX call
    $.ajax({
        type: "POST",
        url: "plugins/magictrigger/core/ajax/magictrigger.ajax.php",
        data: {
            action  : "getStatistics",
            id      : cmd_id,
            dow     : _dow,
            period  : _period,
            interval: _interval,
        },
        dataType: 'json',
        global: true,
        async: false,
        error: function (request, status, error) {
             handleAjaxError(request, status, error);
        },
        success: function (data) {
            //console.log(data);
            if (data.state == 'ok') {
                var points = data.result['points'];
                min_threshold = data.result['minThreshold'];
                max_threshold = data.result['maxThreshold'];
                dow           = data.result['dow'];
                period        = data.result['period'];
                interval      = data.result['interval'];
                for (i = 0; i < points.length; i++) {
                    categories.push(points[i]['time']);
                    series.push(points[i]['value']);
                }
            }
        },
    });

    // Now we have the data, then, draw the graph
    if (series.length > 0) {
        // Update the three selectors (day, period, interval)
        $('#dow').children('option[value="'+ dow +'"]').attr('selected', true);
        $('#period').children('option[value="'+ period +'"]').attr('selected', true);
        $('#interval').children('option[value="'+ interval +'"]').attr('selected', true);

        Highcharts.chart('div_graph', {
            chart: {
                style: {
                  fontFamily: 'Roboto'
                },
                //type: 'spline',
                type: 'column',
                plotBackgroundColor: null,
                plotBackgroundImage: null,
                backgroundColor: null,
                plotBorderWidth: 0,
                plotShadow: false,
                spacingTop: 40,
                spacingLeft: 0,
                spacingRight: 0,
                spacingBottom: 0,
                borderWidth : 0
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: categories
            },
            plotOptions: {
                series: {
                   marker: {
                      enabled: false
                   }
                }
            },
            yAxis: {
                min: 0, 
                max: 100, 
                title: { 
                    text: '{{Probabilite}}' 
                }, 
                allowDecimals: false,
                plotLines: [{
                    value: min_threshold,
                    color: 'red',
                    dashStyle: 'solid',
                    width: 2,
                    label: { 
                       text: '{{Seuil minimum}}'
                    }
                }, {
                    value: max_threshold,
                    color: 'red',
                    dashStyle: 'solid',
                    width: 2,
                    label: {
                      text: '{{Seuil maximum}}'
                    }
                }]
            },
            legend: { 
                enabled: true 
            },
            mapNavigation: {
                enabled: true,
                enableButtons: false
            },
            tooltip: {
                formatter: function () {
                     return this.points.reduce(function (s, point) {
                        return s + '<br/>' + point.series.name + ': ' +
                                        point.y + '';
                     }, '<b>' + this.x + '</b>');
                },
                shared: true
             },
             data: {
                enablePolling: false,
             },
             series: [
             {
                name: '{{Probabilite}}',
                data: series,
             }],
             credits: {
                enabled: false,
             },
             exporting : {
                enabled: false
             },
        });
    } else {
        $('#div_graph').append('<center><span class="label label-danger">{{Pas de donnees}}</span></center>')
    } 

}

$('#div_graph').css('position', 'relative').css('width', '90%');

$('#period').on('change', function () {
	var period   = parseInt($(this).find('option:selected').val());
	var interval = parseInt($('#interval').find('option:selected').val());
    removeMessage();
    if (interval < period) {
        $('#div_message').append('<center><span class="label label-danger">{{l"intervalle ne peut pas etre inferieur a la periodicite}}</span></center>');
    }
});

$('#interval').on('change', function () {
    var interval = parseInt($(this).find('option:selected').val());
    var period   = parseInt($('#period').find('option:selected').val());
    removeMessage();
    if (interval < period) {
        $('#div_message').append('<center><span class="label label-danger">{{l"intervalle ne peut pas etre inferieur a la periodicite}}</span></center>');
    }
});

$('#btn_modal_close').on('click', function () {
	$('#md_modal').dialog('close');
});

$('#btn_modal_show').on('click', function () {

	var dow      = parseInt($('#dow').find('option:selected').val());
	var period   = parseInt($('#period').find('option:selected').val());
	var interval = parseInt($('#interval').find('option:selected').val());

    // remove the displayed message, if any
    if (interval >= period) {
        removeMessage();
        drawGraph(dow, period, interval);
    } 
});

</script>
