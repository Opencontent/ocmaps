{* includo i js e i css necessari *}
{ezscript_require( array( 'leaflet.js') )}
{ezcss_require( array( 'leaflet.css' ) )}
{ezcss_require( array( 'map.css' ) )}

{init($node.node_id)}

{* prendo i punti della geolocalizzazione *}
{def $markerArray = getGeoDataAsArray($view_parameters.status)}

{* prendo i punti appositamente formattati per il fitBounds *}
{def $arrayForFitBounds = getGeoDataForFitBounds()}

{* prendo la lista di marker custom, usata per creare la legenda *}
{def $geoDataStatusArray = getGeoDataStatusArray()}

{def $jsonArea = getJsonArea()}

<div class="content-view-full class-{$node.class_identifier}">

  <div class="content-main">

    <h1>{$node.name|wash()}</h1>

    {if $node.data_map.short_description.has_content}
      <div class="abstract">
        {attribute_view_gui attribute=$node.data_map.short_description}
      </div>
    {/if}

    {* se è stata creata una legenda *}
    {if is_set($geoDataStatusArray)}
        <div class="legenda">

            <div class="row">
                {foreach $geoDataStatusArray as $i => $status}
                <div class="col-xs-4">

                    <a href={concat($node.url_alias|ezurl('no'), '/(status)/', $status.code)} class='legenda-markers-text'><img src={concat('javascript/images/markers/', $status.marker_icon, '.png')|ezdesign()} class='legenda-markers-icon'/>{$status.description}</a>
                    <small class="legenda-markers-count">({$status.count})</small>


                </div>
                {delimiter modulo=3}
            </div>
            <div class="row">
                {/delimiter}
                {/foreach}
            </div>

        </div>
    {/if}

  </div>

  <div class="content-main">

    {* renderizza la mappa *}
    <div id="map-{$node.node_id}" style="height: {$node.data_map.map_height.data_int}px;"></div>


    {* testo della descrizione dell'oggetto mappa *}
    {if $node.data_map.short_description.has_content}
      <div class="body">
        {attribute_view_gui attribute=$node.data_map.description}
      </div>
    {/if}


    <script>

        var map = L.map('map-{$node.node_id}');
        var markers = [];

        {* fix per chiamata ajax da frontend *}
        L.Icon.Default.imagePath = {'javascript/images'|ezdesign()};

        {* scorro tutti i markers  *}
        {if is_set($markerArray)}

            {foreach $markerArray as $i => $child}

                {* creo l'icona da mostarre per il marker (se esiste) *}
                {if is_set($child.marker_icon)}
                    {literal}
                        var customIcon = L.icon({
                            iconUrl: {/literal}{concat('javascript/images/markers/', $child.marker_icon, '.png')|ezdesign()}{literal},
                            shadowUrl: {/literal}{concat('javascript/images/markers/', $child.marker_icon, '-shadow.png')|ezdesign()}{literal},
                        });
                    {/literal}

                    var marker = L.marker([{$child.lat},{$child.lon}], {literal}{icon: customIcon}{/literal}).addTo(map);

                {* altrimenti uso le icone di default *}
                {else}

                    var marker = L.marker([{$child.lat},{$child.lon}]).addTo(map);

                {/if}

                {* creo il testo del popup *}

                {if eq(showLinkInPopup(), 1)}
                    marker.bindPopup("<a href='{$child.urlAlias}'>{$child.popupMsg|wash('xhtml')}</a>");
                {else}
                    marker.bindPopup("{$child.popupMsg|wash('xhtml')}");
                {/if}

                markers[{$i}] = marker;

            {/foreach}

        {/if}

        {* centro la mappa in un intorno dei markers *}
        map.fitBounds({$arrayForFitBounds});

        {* carico il layer di openstreetmap *}
        {literal}

            L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18
            }).addTo(map);

            $(document).ready
            (
                function(){
                    $('.list-markers-text a').bind
                    ('click',
                        function(e){
                            var id = $(this).data('id');
                                markers[id].openPopup();
                                e.preventDefault();
                        }
                    );
                }
            );

        {/literal}
        {if is_set($jsonArea)}
        {literal}

        //json che definisce un area in chiaro, rendendo opaco il resto della mappa
        var area = {/literal}{$jsonArea}{literal};

            var coordinates = area.features[0].geometry.coordinates[0];
            var latLngs = [];

            for (i=0; i<coordinates.length; i++) {
                latLngs.push(new L.LatLng(coordinates[i][1], coordinates[i][0]));
            }

            var polygon = new L.Polygon(latLngs, {color: '#ffffff'});
            map.addLayer(polygon);

            L.Mask = L.Polygon.extend({
                options: {
                    stroke: false,
                    color: '#333',
                    fillOpacity: 0.5,
                    clickable: true,


                    outerBounds: new L.LatLngBounds([-90, -360], [90, 360])
                },

                initialize: function (latLngs, options) {

                    var outerBoundsLatLngs = [
                        this.options.outerBounds.getSouthWest(),
                        this.options.outerBounds.getNorthWest(),
                        this.options.outerBounds.getNorthEast(),
                        this.options.outerBounds.getSouthEast()
                    ];
                    L.Polygon.prototype.initialize.call(this, [outerBoundsLatLngs, latLngs], options);
                }

            });
            L.mask = function (latLngs, options) {
                return new L.Mask(latLngs, options);
            };

            L.mask(latLngs).addTo(map);

        {/literal}

        {/if}

    </script>

  </div>

    {* lista dei nomi dei marker nell'area destra *}
     <div class="content-related"  style="height: {$node.data_map.map_height.data_int}px; overflow: auto;">

        {* prendo i punti della geolocalizzazione *}

        {if is_set($markerArray)}

            {if eq(showFilterSelect(), 1)}

                {if is_set($geoDataStatusArray)}

                    <select id="geoDataStatusSelect" class="selectpicker" data-live-search="true">

                    <option value="">Tutti gli stati</option>

                    {foreach $geoDataStatusArray as $i => $status}

                        {def $selected=''}

                        {if eq($view_parameters.status, $i)}
                            {set $selected = 'selected'}
                        {/if}
                        <option value="{$i}" {$selected}>{$status.description}</option>
                    {/foreach}

                    </select>

                {/if}

            {/if}

            <ul class="list-markers-text">
                {foreach $markerArray as $i => $child}

                    <li>
                        <a href="{$child.urlAlias|ezurl("no")}" data-id="{$i}">
                            {$child.name}
                        </a>
                    </li>

                {/foreach}
            </ul>

        {/if}

     </div>

</div>


{literal}

    <script>
        $("#geoDataStatusSelect").change(function() {
            var value = $(this).children(":selected").attr("value");
            $(location).attr('href','{/literal}{$node.url_alias|ezurl("no")}{literal}/(status)/'+value);
        });
    </script>
{/literal}