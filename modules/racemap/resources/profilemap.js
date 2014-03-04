
  var elevator;
  var map;
  var chart;
  var infowindow = new google.maps.InfoWindow();
  var polyline;

  // The following path marks a general path from Mt.
  // Whitney, the highest point in the continental United
  // States to Badwater, Death Vallet, the lowest point.
  var whitney = new google.maps.LatLng(36.578581, -118.291994);
  var lonepine = new google.maps.LatLng(36.606111, -118.062778);
  var owenslake = new google.maps.LatLng(36.433269, -117.950916);
  var beattyjunction = new google.maps.LatLng(36.588056, -116.943056);
  var panamintsprings = new google.maps.LatLng(36.339722, -117.467778);
  var badwater = new google.maps.LatLng(36.23998, -116.83171);
  
  // Load the Visualization API and the columnchart package.
  google.load("visualization", "1", {packages: ["columnchart"]});

  function initialize() {
    var myOptions = {
      zoom: 8,
      center: lonepine,
      mapTypeId: 'terrain'
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);

    // Create an ElevationService.
    elevator = new google.maps.ElevationService();

    // Draw the path, using the Visualization API and the Elevation service.
    drawPath();
  }

  function drawPath() {

    // Create a new chart in the elevation_chart DIV.
    chart = new google.visualization.ColumnChart(document.getElementById('elevation_chart'));

    var path = [ whitney, lonepine, owenslake, panamintsprings, beattyjunction, badwater];

    // Create a PathElevationRequest object using this array.
    // Ask for 256 samples along that path.
    var pathRequest = {
      'path': path,
      'samples': 256
    }

    // Initiate the path request.
    elevator.getElevationAlongPath(pathRequest, plotElevation);
  }

  // Takes an array of ElevationResult objects, draws the path on the map
  // and plots the elevation profile on a Visualization API ColumnChart.
  function plotElevation(results, status) {
    if (status == google.maps.ElevationStatus.OK) {
      elevations = results;

      // Extract the elevation samples from the returned results
      // and store them in an array of LatLngs.
      var elevationPath = [];
      for (var i = 0; i < results.length; i++) {
        elevationPath.push(elevations[i].location);
      }

      // Display a polyline of the elevation path.
      var pathOptions = {
        path: elevationPath,
        strokeColor: '#0000CC',
        opacity: 0.4,
        map: map
      }
      polyline = new google.maps.Polyline(pathOptions);

      // Extract the data from which to populate the chart.
      // Because the samples are equidistant, the 'Sample'
      // column here does double duty as distance along the
      // X axis.
      var data = new google.visualization.DataTable();
      data.addColumn('string', 'Sample');
      data.addColumn('number', 'Elevation');
      for (var i = 0; i < results.length; i++) {
        data.addRow(['', elevations[i].elevation]);
      }

      // Draw the chart using the data within its DIV. 
      document.getElementById('elevation_chart').style.display = 'block';
      chart.draw(data, {
        width: 640,
        height: 200,
        legend: 'none',
        titleY: 'Elevation (m)'
      });
    }
  }  

