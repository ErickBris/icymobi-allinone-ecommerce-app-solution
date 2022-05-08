/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Stephen Pham, Phong Nguyen
 * Author URI: http://inspius.com
 */

(function(a,b,c,d){function h(b,c){this.options=a.extend(!0,{},e,c),this.input=b,this.$input=a(b),this._defaults=e,this._name="geocomplete",this.init()}var e={bounds:!0,country:null,map:!1,details:!1,detailsAttribute:"name",location:!1,mapOptions:{zoom:14,scrollwheel:!1,mapTypeId:"roadmap"},markerOptions:{draggable:!1},maxZoom:16,types:["geocode"],blur:!1},f="street_address route intersection political country administrative_area_level_1 administrative_area_level_2 administrative_area_level_3 colloquial_area locality sublocality neighborhood premise subpremise postal_code natural_feature airport park point_of_interest post_box street_number floor room lat lng viewport location formatted_address location_type bounds".split(" "),g="id url website vicinity reference name rating international_phone_number icon formatted_phone_number".split(" ");a.extend(h.prototype,{init:function(){this.initMap(),this.initMarker(),this.initGeocoder(),this.initDetails(),this.initLocation()},initMap:function(){if(this.options.map){if("function"==typeof this.options.map.setCenter)return this.map=this.options.map,d;this.map=new google.maps.Map(a(this.options.map)[0],this.options.mapOptions),google.maps.event.addListener(this.map,"click",a.proxy(this.mapClicked,this))}},initMarker:function(){if(this.map){var b=a.extend(this.options.markerOptions,{map:this.map});b.disabled||(this.marker=new google.maps.Marker(b),google.maps.event.addListener(this.marker,"dragend",a.proxy(this.markerDragged,this)))}},initGeocoder:function(){var b={types:this.options.types,bounds:this.options.bounds===!0?null:this.options.bounds,componentRestrictions:this.options.componentRestrictions};this.options.country&&(b.componentRestrictions={country:this.options.country}),this.autocomplete=new google.maps.places.Autocomplete(this.input,b),this.geocoder=new google.maps.Geocoder,this.map&&this.options.bounds===!0&&this.autocomplete.bindTo("bounds",this.map),google.maps.event.addListener(this.autocomplete,"place_changed",a.proxy(this.placeChanged,this)),this.$input.keypress(function(a){return 13===a.keyCode?!1:d}),this.$input.bind("geocode",a.proxy(function(){this.find()},this)),this.options.blur===!0&&this.$input.blur(a.proxy(function(){this.find()},this))},initDetails:function(){function e(a){d[a]=b.find("["+c+"="+a+"]")}if(this.options.details){var b=a(this.options.details),c=this.options.detailsAttribute,d={};a.each(f,function(a,b){e(b),e(b+"_short")}),a.each(g,function(a,b){e(b)}),this.$details=b,this.details=d}},initLocation:function(){var b,a=this.options.location;if(a){if("string"==typeof a)return this.find(a),d;a instanceof Array&&(b=new google.maps.LatLng(a[0],a[1])),a instanceof google.maps.LatLng&&(b=a),b&&(this.map&&this.map.setCenter(b),this.marker&&this.marker.setPosition(b))}},find:function(a){this.geocode({address:a||this.$input.val()})},geocode:function(b){this.options.bounds&&!b.bounds&&(b.bounds=this.options.bounds===!0?this.map&&this.map.getBounds():this.options.bounds),this.options.country&&(b.region=this.options.country),this.geocoder.geocode(b,a.proxy(this.handleGeocode,this))},handleGeocode:function(a,b){if(b===google.maps.GeocoderStatus.OK){var c=a[0];this.$input.val(c.formatted_address),this.update(c),a.length>1&&this.trigger("geocode:multiple",a)}else this.trigger("geocode:error",b)},trigger:function(a,b){this.$input.trigger(a,[b])},center:function(a){a.viewport?(this.map.fitBounds(a.viewport),this.map.getZoom()>this.options.maxZoom&&this.map.setZoom(this.options.maxZoom)):(this.map.setZoom(this.options.maxZoom),this.map.setCenter(a.location)),this.marker&&(this.marker.setPosition(a.location),this.marker.setAnimation(this.options.markerOptions.animation))},update:function(a){this.map&&this.center(a.geometry),this.$details&&this.fillDetails(a),this.trigger("geocode:result",a)},fillDetails:function(b){var c={},d=b.geometry,e=d.viewport,f=d.bounds;a.each(b.address_components,function(a,b){var d=b.types[0];c[d]=b.long_name,c[d+"_short"]=b.short_name}),a.each(g,function(a,d){c[d]=b[d]}),a.extend(c,{formatted_address:b.formatted_address,location_type:d.location_type||"PLACES",viewport:e,bounds:f,location:d.location,lat:d.location.lat(),lng:d.location.lng()}),a.each(this.details,a.proxy(function(a,b){var d=c[a];this.setDetail(b,d)},this)),this.data=c},setDetail:function(a,b){b===d?b="":"function"==typeof b.toUrlValue&&(b=b.toUrlValue()),a.is(":input")?a.val(b):a.text(b)},markerDragged:function(a){this.trigger("geocode:dragged",a.latLng)},mapClicked:function(a){this.trigger("geocode:click",a.latLng)},resetMarker:function(){this.marker.setPosition(this.data.location),this.setDetail(this.details.lat,this.data.location.lat()),this.setDetail(this.details.lng,this.data.location.lng())},placeChanged:function(){var a=this.autocomplete.getPlace();a.geometry?this.update(a):this.find(a.name)}}),a.fn.geocomplete=function(b){var c="plugin_geocomplete";if("string"==typeof b){var d=a(this).data(c)||a(this).geocomplete().data(c),e=d[b];return"function"==typeof e?(e.apply(d,Array.prototype.slice.call(arguments,1)),a(this)):(2==arguments.length&&(e=arguments[1]),e)}return this.each(function(){var d=a.data(this,c);d||(d=new h(this,b),a.data(this,c,d))})}})(jQuery,window,document);

(function( $ ){
	"use strict";
    var InspiusIcymobiSetup = {
        init : function() {
            this.contact.init();
        	this.tabs.init();
            this.saveOption();
        },
        saveOption: function(){
            $('#icymobi_save_option').on('click', function(event) {
                tinyMCE.triggerSave();
                var data = JSON.stringify($('#icymobi_theme_options').addClass('is-loading').serializeArray());
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'icymobi_save_option',
                        data: data
                    },
                    success: function(response){
                        $('#icymobi_theme_options').removeClass('is-loading');
                    }
                });
                $('.is-icymobi-wrap>.message').removeClass('error').slideUp(400);
                event.preventDefault();
            });
        },
        contact: {
            init: function(){
                var self = this;
                $("#geocomplete").geocomplete({
                    map: ".map_canvas",
                    types: ["establishment"],
                    details: ".mapdetail",
                    detailsAttribute: 'data-geo',
                    markerOptions: {
                        draggable: true
                    },
                    location: self.getLocation(),
                    mapOptions: {
                        scrollwheel :true,
                        zoom:15,
                        // center: 'NYC'
                    }
                });

                $("#geocomplete").bind("geocode:dragged", function(event, latLng){
                    $("input[name=contact_map_lat]").val(latLng.lat());
                    $("input[name=contact_map_lng]").val(latLng.lng());
                    // $("#reset").show();
                });
            },
            getLocation: function(){
                return $("input[name=contact_map_lat]").val() + ',' + $("input[name=contact_map_lng]").val();
            }
        },
        tabs: {
        	init: function(){
        		var self = this;
        		$('.is-icymobi-wrap .nav-tab').on('click', function(event) {
        			var $this = $(this);
        			if($this.hasClass('nav-tab-active')){
        				return false;
        			}else{
        				$('.is-icymobi-wrap .nav-tab').removeClass('nav-tab-active');
        				$this.addClass('nav-tab-active');
        				self.viewContent($this.attr('href'));
        			}
        			event.preventDefault();
        			/* Act on the event */
        		});

                this.subInit();
        	},
        	viewContent: function(id){
        		$('.is-icymobi-wrap .tab-content').removeClass('active');
        		$(id).addClass('active');
                if(id=='#icymobi-contact-config'){
                    $("#geocomplete").trigger("geocode");
                    google.maps.event.trigger(window, 'resize', {});
                }
        	},
            subInit: function(){
                $('.is-icymobi-wrap .icymobi-subtab a').on('click', function(event) {
                    var $this = $(this),
                    $parent = $this.closest('.icymobi-subtab');
                    if($this.hasClass('active')){
                        return false;
                    }else{
                        $parent.next().find('.sub-tab-content').removeClass('active');
                        $($this.attr('href')).addClass('active');
                    }

                    $parent.find('a').removeClass('active');
                    $this.addClass('active');

                    event.preventDefault();
                });
            }
        }
    };

    $(document).ready(function() {
    	InspiusIcymobiSetup.init();
    });

})( jQuery );