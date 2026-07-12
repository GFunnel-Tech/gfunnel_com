function AqbMap(selector, options) {
	this.options = options;
	this.selector = selector;
	this.map_object = null;
	this.info_window = null;
	this.markers = null;
	this.loaded_all = false;
	this.filter = '';

	this.init_map();
}

AqbMap.prototype.init_map = function() {
	this.map_object = L.map(this.selector);

	if (!this.options.admin_mode) {
		var $this = this;
		this.map_object.once('layeradd', function() {
		    $this.redrawMarkers(true);

		    $this.map_object.on('zoomlevelschange', function(){
				$this.redrawMarkers(false);
			});

			$this.map_object.on('moveend', function(){
				$this.redrawMarkers(false);
			});
		});
	}

	this.map_object.setView([this.options.lat, this.options.lng], this.options.zoom);
	L.tileLayer(this.options.url, this.options).addTo(this.map_object);
    this.info_window = L.popup();
}

AqbMap.prototype.on_view_change = function(callback) {
	this.map_object.on('zoomlevelschange', callback);
	this.map_object.on('moveend', callback);
}


AqbMap.prototype.getMapView = function() {
	var o = this.map_object.getCenter();
	return {zoom: this.map_object.getZoom(), lat: o.lat, lng: o.lng};
}

AqbMap.prototype.deleteMarkers = function() {
	if (this.markers && this.markers.length) {
		for (i in this.markers) {
			this.markers[i].remove();
		}
	}
	this.markers = null;
}

AqbMap.prototype.redrawMarkers = function(bInit) {
	if (this.loaded_all) return;

	bx_loading('aqb_map_container', true);

	this.deleteMarkers();

	var $this = this;

	var bounds = this.map_object.getBounds();
	var oParams = {
		randseed: this.options.randseed,
		filter: this.filter,
		lng_min: bounds.getEast(),
		lng_max: bounds.getWest(),
		lat_min: bounds.getNorth(),
		lat_max: bounds.getSouth()
	};

	if (bInit) {
		oParams.init = 1;
		this.setFilter(this.options.filter);
	}

	$.get(sUrlRoot+'modules/index.php?r=aqb_locations_map/get_locations', oParams, function(oResponse){
		bx_loading('aqb_map_container', false);
		if (!oResponse.total) return;

		$this.markers = new Array();
		for (i in oResponse.locations) {
			var icon = null;
			if ($this.options.locations_icons[oResponse.locations[i].type]) {
				icon = L.icon({
			        iconUrl: $this.options.locations_icons[oResponse.locations[i].type],
			        iconAnchor: [16, 16],
			        iconSize: [32, 32]
			    });
			}
			var oMarker = L.marker([parseFloat(oResponse.locations[i].lat), parseFloat(oResponse.locations[i].lng)], {
				title: oResponse.locations[i].type == 'online_profiles' ? _t('_aqb_locations_map_online_profiles') : _t('_'+oResponse.locations[i].type)
			});
			if (icon) oMarker.setIcon(icon);

			if (bInit && $.inArray(oResponse.locations[i].type, $this.options.filter) != -1 || !bInit) oMarker.addTo($this.map_object);

		    oMarker.type = oResponse.locations[i].type;
		    oMarker.object_id = oResponse.locations[i].object_id;
		    oMarker.on('click', function() {
		    	$this.showMarkerInfo(this);
		    });
		    $this.markers.push(oMarker);
		}

		if (bInit && oResponse.all) $this.loaded_all = true;
	}, 'json');
}

AqbMap.prototype.applyMarkersFilter = function(aFilter) {
	this.setFilter(aFilter);
	if (this.loaded_all) {
		for (i in this.markers) {
			if ($.inArray(this.markers[i].type, aFilter) != -1) {
				if (this.markers[i]._map == null)
					this.markers[i].addTo(this.map_object);
			} else {
				this.markers[i].remove();
			}
		}
	} else {
		this.redrawMarkers(false);
	}
}

AqbMap.prototype.setFilter = function(aFilter) {
	var sFilter = '';

	for (i in aFilter) {
		sFilter += aFilter[i] + ',';
	}

	if (sFilter.length) sFilter = sFilter.substr(0, sFilter.length - 1);
	else sFilter = 'empty';

	this.filter = sFilter;
}

AqbMap.prototype.showMarkerInfo = function(oMarker) {
	bx_loading('aqb_map_container', true);

	var $this = this;
	var loc = oMarker.getLatLng();

	$.get(sUrlRoot+'modules/index.php?r=aqb_locations_map/get_location_info/'+loc.lat+'/'+loc.lng+'/'+this.filter, function(sResponse){
		bx_loading('aqb_map_container', false);
		if (sResponse.length) {
			$this.info_window
				.setLatLng(oMarker.getLatLng())
		        .setContent(sResponse)
		        .openOn($this.map_object);
		}
	});
}
