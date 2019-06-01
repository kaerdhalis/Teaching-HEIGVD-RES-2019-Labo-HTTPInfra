var Chance = require('chance');
var chance = new Chance();

var express = require('express');
var app = express();

app.get('/', function (req, res) {
  res.send(generateLocation());
});

app.listen(3000, function () {
  console.log('app listening on port 3000!');
});

function generateLocation(){

var numberOfCity = chance.integer({min: 0,max: 15});
console.log(numberOfCity);

var cities = [];
for(var i = 0; i<numberOfCity;i++){

var country = chance.country({ full: true });
var state = chance.state({full: true });
cities.push({country: country,state:state,city:chance.city()});
};
console.log(cities);
return cities;
}
