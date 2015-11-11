/*  ::

    :: Theme        : Jets
    :: Theme URI    : http://labs.funcoders.com/html/Jets

    :: File         : map.js
    :: About        : Google map
    :: Version      : 1.4.4
    ::
    :: Find the Latitude and Longitude of your address:
    :: - http://universimmedia.pagesperso-orange.fr/geo/loc.htm
    :: - http://www.findlatitudeandlongitude.com/find-address-from-latitude-and-longitude/
::  */


$(function () {

    var googlemaps = $("#googlemaps");
    if (googlemaps.length) {

        var markers = [{
            html        :   '<h4>Office №1:</h4>'+
                            '<address>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Address:</b></div>'+
                                    '<div class="col-xs-8">322 Victoria Street,<br>Darlinghurst NSW 2010,<br>Australia</div>'+
                                '</div>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Phone:</b></div>'+
                                    '<div class="col-xs-8">1800-2233-4455</div>'+
                                '</div>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Fax:</b></div>'+
                                    '<div class="col-xs-8">1800-2233-4455</div>'+
                                '</div>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Email:</b></div>'+
                                    '<div class="col-xs-8"><a href="mailto:victoria@yoursite.com">victoria@yoursite.com</a></div>'+
                                '</div>'+
                            '</address>',
            latitude    : -33.87695388579145,
            longitude   : 151.22183918952942
        },{
            html        :   '<h4>Office №2:</h4>'+
                            '<address>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Address:</b></div>'+
                                    '<div class="col-xs-8">26 Macdonald Street,<br />Paddington NSW 2021,<br />Australia</div>'+
                                '</div>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Phone:</b></div>'+
                                    '<div class="col-xs-8">1800-6677-8899</div>'+
                                '</div>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Fax:</b></div>'+
                                    '<div class="col-xs-8">1800-6677-8899</div>'+
                                '</div>'+
                                '<div class="row">'+
                                    '<div class="col-xs-4"><b>Email:</b></div>'+
                                    '<div class="col-xs-8"><a href="mailto:macdonald@yoursite.com">macdonald@yoursite.com</a></div>'+
                                '</div>'+
                            '</address>',
            latitude: -33.88115365546491,
            longitude: 151.2246260046959
        }],
        latitude    = -33.87895388579145,
        longitude   = 151.22283918952942,
        zoom        = 16,
        img         = "img/pin.png";

        google.maps.event.addDomListener(window, 'load', function() {

            var map = new google.maps.Map(document.getElementById('googlemaps'), {
                zoom    : zoom,
                center  : new google.maps.LatLng(latitude, longitude)
            }),
            infowindow = new google.maps.InfoWindow(),
            marker, i;

            for (i = 0; i < markers.length; i++) {

                marker = new google.maps.Marker({
                    position    : new google.maps.LatLng(markers[i].latitude, markers[i].longitude),
                    map         : map,
                    icon        : img
                });

                google.maps.event.addListener(marker, 'click', (function(marker, i) {
                    return function() {
                        infowindow.setContent(markers[i].html);
                        infowindow.open(map, marker);
                    }
                })(marker, i));

            }

            google.maps.event.addDomListener(window, 'resize', function() {
                google.maps.event.trigger(map, 'resize');
                map.setCenter(new google.maps.LatLng(latitude, longitude));
            });

        });
    }

});