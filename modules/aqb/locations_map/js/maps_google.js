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
    this.map_object = new google.maps.Map(document.getElementById(this.selector), {
        zoom : this.options.zoom,
        center: {lat: this.options.lat, lng: this.options.lng},
        mapTypeId : google.maps.MapTypeId.ROADMAP
    });

    if (!this.options.admin_mode) {
	    var $this = this;
		google.maps.event.addListenerOnce(this.map_object, 'idle', function(){
		    $this.redrawMarkers(true);

		    $this.map_object.addListener('dragend', function(){
				$this.redrawMarkers(false);
			});

			$this.map_object.addListener('zoom_changed', function(){
				$this.redrawMarkers(false);
			});
		});
	}

    this.info_window = new google.maps.InfoWindow();
}

AqbMap.prototype.on_view_change = function(callback) {
	this.map_object.addListener('dragend', callback);
	this.map_object.addListener('zoom_changed', callback);
}

AqbMap.prototype.getMapView = function() {
	var o = this.map_object.getCenter();
	return {zoom: this.map_object.getZoom(), lat: o.lat(), lng: o.lng()};
}

AqbMap.prototype.deleteMarkers = function() {
	if (this.markers && this.markers.length) {
		for (i in this.markers) {
			this.markers[i].setMap(null);
		}
	}
	this.markers = null;
}

AqbMap.prototype.redrawMarkers = function(bInit) {
	if (this.loaded_all) return;

	bx_loading('aqb_map_container', true);

	this.deleteMarkers();

	var $this = this;

	var bounds = this.map_object.getBounds().toJSON();
	var oParams = {
		randseed: this.options.randseed,
		filter: this.filter,
		lng_min: bounds.east,
		lng_max: bounds.west,
		lat_min: bounds.north,
		lat_max: bounds.south
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
				icon = {
			        url: $this.options.locations_icons[oResponse.locations[i].type],
			        anchor: new google.maps.Point(16, 16),
			        scaledSize: new google.maps.Size(32, 32)
			    }
			}

			var oMarker = new google.maps.Marker({
				position: {lat: parseFloat(oResponse.locations[i].lat), lng: parseFloat(oResponse.locations[i].lng)},
				// map: $this.map_object,
				title: oResponse.locations[i].type == 'online_profiles' ? _t('_aqb_locations_map_online_profiles') : _t('_'+oResponse.locations[i].type),
				optimized: false,
				icon: icon
		    });

			if (bInit && $.inArray(oResponse.locations[i].type, $this.options.filter) != -1 || !bInit) oMarker.setMap($this.map_object);

		    oMarker.type = oResponse.locations[i].type;
		    oMarker.object_id = oResponse.locations[i].object_id;
		    oMarker.addListener('click', function() {
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
				if (this.markers[i].getMap() == null) this.markers[i].setMap(this.map_object);
			} else {
				this.markers[i].setMap(null);
			}
		}
	} else {
		this.setFilter(aFilter);
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

	$.get(sUrlRoot+'modules/index.php?r=aqb_locations_map/get_location_info/'+oMarker.position.lat()+'/'+oMarker.position.lng()+'/'+this.filter, function(sResponse){
		bx_loading('aqb_map_container', false);
		if (sResponse.length) {
			$this.info_window.setContent(sResponse);
			$this.info_window.open($this.map_object, oMarker);
		}
	});
}