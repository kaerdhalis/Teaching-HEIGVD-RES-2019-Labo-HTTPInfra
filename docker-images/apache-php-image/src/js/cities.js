$(function(){
	
console.log("loading cities");
function loadCities(){
        $.getJSON( "/api/cities/", function( cities ) {
            console.log(cities);

            var message = "No city found";

            if(cities.length > 0){
                message = cities[0].city + " " + cities[0].country;
            }

            $(".cities").text( message);
        });
    }

    loadCities();
    setInterval(loadCities, 2000);

    console.log("City loaded");


});
