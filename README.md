# Teaching-HEIGVD-RES-2018-Labo-HTTPInfra

## Step 1: Static HTTP server with apache httpd

The objective of this step is to implement a dockerised static http server.
#### Dockerfile

We used  the [7.2](https://hub.docker.com/_/php/)  **apache PHP**  docker image.

we copied our src/ folder containing the boostrap web template and the index.html file into the folder /var/www/html/ of the container

```dockerfile
FROM php:7.0-apache

COPY src/ /var/www/html/
```

#### Build and test
We build and start our docker container, using the following bash command.

```bash
$ docker build -t res/apache_php .					# Builds a new docker image containing the apache php server and our  webpage, that we will name res/apache_php
$ docker run -d -p 8080:80 res/apache_php			# Run our freshly created docker image in background.
```
We also mapped the port 80 of the container to the port 8080 of our machine.

To test it :

```bash
$ telnet localhost 8080
```
or using a web browser with the url ``localhost:8080``

## Step 2: Dynamic HTTP server with express.js

The goal of this step is  to create a dynamic web page using node.js which can generate a different payload at each Run

#### Dockerfile

We used the [8.11](https://hub.docker.com/_/node/)  **node.js**  docker image.

We copied our src/ folder containing the node.js script in the folder /opt/app of the container.

Then we indicated that every time we run the container it will execute the script.

```dockerfile
FROM node:8.11

COPY src /opt/app

CMD ["node", "/opt/app/index.js"]
```

### Node.js

```javascript
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
```

#### Build and test

We use  node 8.11 and the module Chance to get random value of city name and country name.

```bash
$ docker build -t res/express_cities
$ docker run -p 8080:3000 res/express_cities
```
To test it :

```bash
$ telnet localhost 8080

GET / HTTP/1.0

```
We should retrieve a random list of cities.

## Step 3: Reverse proxy with apache (static configuration)

The goalof this step is to create a proxy to reach the containers of the last steps with a single address. We choose to use the same address as the webcast. The address is ``demo.res.ch``.

### Dockerfile

The image is based on the same apache image as step 1

we copy the conf/ folder containing the configuration of the available sites into the folder /etc/apache/ of the container.

We enable the modules ``proxy`` and ``proxy_http`` with the command ``a2enmod``. The sites are enabled with the method ``a2ensite``.

```dockerfile
FROM php:7.0-apache

COPY conf/ /etc/apache2

RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```

### VirtualHosts

In the ``conf`` folder, we have a folder ``sites-available`` with 2 Virtualhosts inside. The 2 files are ``000-default.conf`` and ``01-reverse-proxy.conf``. The content of ``01-reverse-proxy.conf`` file that we added is  below.


```dockerfile
# 001-reverse-proxy.conf
<VirtualHost *:80>

	ServerName demo.res.ch

	ProxyPass "/api/cities/" "http://172.17.0.3:3000/"
	ProxyPassReverse "/api/cities/" "http://172.17.0.3:3000/"

	ProxyPass "/" "http://172.17.0.2:80/"
	ProxyPassReverse "/" "http://172.17.0.2:80/"

</VirtualHost>
```

We can see in the reverse-proxy configuration file that we hard coded the IP addresses of the two different container. To get the IP addresses

```bash
inspect docker inspect <container_name> | grep -i ipaddress
```

Here's the result :

| Container name  | Address:port    |
| --------------- | --------------- |
| apache_static   | 172.17.0.2:80   |
| express_dynamic | 172.17.0.3:3000 |
| apache_rp       | 172.17.0.4:8080 |

This value can be different depending on wich container we start first and if we have different containers running so it's a good thing to check that the docker have the good harcoded IP before running the proxy

### Hosts file

The browser need to send a specific header to know where redirect the request. To do that, we have to change the DNS configuration :
```bash
$ sudo vi /etc/hosts # Sudo is to give the save right
```

This is the content of the hosts file
```dockerfile
127.0.0.1       localhost demo.res.ch
127.0.1.1       Menren

# The following lines are desirable for IPv6 capable hosts
::1     ip6-localhost ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters

```

### Build it and test it
We have to launch the container in a specific order so the IP adress match the hardcoded one.

```bash
$ docker run --name apache_static res/apache_php
$ docker run --name express_dynamic res/express_movies
$ docker build -t res/apache_rp .
$ docker run --name apache_rp -p 8080:80 res/apache_rp
```

Then, to test it, you have to launch a browser and navigate on the address ``demo.res.ch:8080`` to access to the ``apache_static`` container and navigate on the address ``demo.res.ch:8080/api/cities/`` to access to the ``express_dynamic`` container.

## Step 4: AJAX requests with JQuery

The goal of this step is to setup an AJAX request with JQuery. The request will display in the webpage the first city get in the ``demo.res.ch/api/cities/`` page.

### Javascript

We create the javascript file in the ``apache-php-image/src/js/`` folder with the others javascript files. Below, the content of the file ``cities.js``

```javascript
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
```

When the page is ready, we get some random cities from the page ``/api/cities/``. Then, we edit a HTML tag to write the next city. We display  the name of the city and the country. In the last part, we set an interval to reload the cities. Here, we reload the cities every 2 seconds.

### HTML

To see the cities, we edit the header to change the class name to "cities" as shown below :

```html
<!-- Masthead -->
  <header class="masthead">
    <div class="container h-100">
      <div class="row h-100 align-items-center justify-content-center text-center">
        <div class="col-lg-10 align-self-end">
          <h1 class="text-uppercase text-white font-weight-bold">Welcome to Kaerdhalis' Res Lab</h1>
          <hr class="divider my-4">
        </div>
        <div class="col-lg-8 align-self-baseline">
          <p class="cities">Start Bootstrap can help you build better websites using the Bootstrap framework! Just download a theme and start customizing, no strings attached!</p>
          <a class="btn btn-primary btn-xl js-scroll-trigger" href="#about">Find Out More</a>
        </div>
      </div>
    </div>
  </header>
```

Then we import the javascript file :

``` html
<!-- Custom script to load cities -->
    <script src="js/cities.js"></script>
```

### Test it

To test the AJAX request, start by running the container. Be careful to start its in the right order :

```bash
$ docker run --name apache_static res/apache_php
$ docker run --name express_dynamic res/express_movies
$ docker run --name apache_rp -p 8080:80 res/apache_rp
```

Then, access to the address ``demo.res.ch`` with a web browser and see the last text in the header change every 2 seconds.

## Step 5: Dynamic reverse proxy configuration

The goal of this step is to generate the reverse proxy's static web server and and dynamic web server ip address dynamicly, rather then having them staticly written in the reverse proxy container configuration file.For this we will use the environment variables to pass the IP adress to the server


The environment variables will be set in the container. For this, we'll need to add the **-e** parameter to the command, followed by our variables and their value, like this :

```bash
$ docker run -d -e <variable1>=<value1> -e <variable2>=<value2> .... -p 8080:80 res/apache_rp
```

This will allow us to run a container and use informations given in those environment variables without modifying the container.

On the [github](https://github.com/docker-library/php/tree/78125d0d3c32a87a05f56c12ca45778e3d4bb7c9/7.0/stretch/apache) page of the php apache server, we can find a dockerfile, in which appears the following lines at the end :

```dockerfile
COPY apache2-foreground /usr/local/bin/
WORKDIR /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]		# last line of the Dockerfile
```

So, when running the docker container, it will run the script called **apache2-foreground**.
We are going to create a new script, with the same way, and replace the old one. What it would do, is exactly the same, but we'll add a few commands to go and get our environment variable and do something with them.

So what we first did was to create a new script, called  **apache2-foreground**, and copy paste the old script in thise new file.
Then, we added those 3 lines :

```bash
# Labo HTTP RES
echo "Setup for the RES lab"
echo "Static app URL  : $STATIC_APP"
echo "Dynamic app URL : $DYNAMIC_APP"
```

This allows us to be sure that the variables ``$STATIC_APP`` and ``$DYNAMIC_APP`` have been found, and show their value.
What we have left to do is to modify the Dockerfile. We want to copy the new script in then container when building the container, so once we run it, it will run the new one :

```dockerfile
FROM php:7.0-apache

# Adding our apache script in the bin directory
COPY apache2-foreground /usr/local/bin/

COPY conf/ /etc/apache2

RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```


We then create a php script to get those variable and create a configuration file that we will use like  in the step 4.
This configuration file will contain the address and port on which the static webserver and the dynamic web server will run.

First, we create a templates directory, in which we create a file named **config-template.php**.
The configuration file we are going to create is the same one we created with our reverse proxy : **001-reverse-proxy.conf**, except this one will have a dynamic address based on the environment variables:


```php
<?php
$dynamic_app = getenv('DYNAMIC_APP');
$static_app  = getenv('STATIC_APP');
?>

<VirtualHost *:80>
	ServerName demo.res.ch

	ProxyPass '/api/cities/' 'http://<?php print $dynamic_app ?>/'
	ProxyPassReverse '/api/cities/' 'http://<?php print $dynamic_app ?>/'

	ProxyPass '/' 'http://<?php print $static_app ?>/'
	ProxyPassReverse '/' 'http://<?php print $static_app ?>/'
</VirtualHost>
```

So what this script does is getting the environments variables value and print them at the previous address place.
Note that we could have used a bash script, using the ```$echo``` command aswell.

We now have a script creating a dynamic configuration file for our reverse proxy container.


What we have to do now is to write this configuration in the configuration location once the container is runed.
So we first need to modify our dockerfile, for him to take our template and put it in the container :

```dockerfile
#Image Docker du serveur Apache
FROM php:7.2-apache

#Installation de vim
RUN apt-get update && \
apt-get install -y vim

#Copie le code source dans le container
COPY apache2-foreground /usr/local/bin/
COPY template /var/apache2/template
COPY conf/ /etc/apache2

#enable les sites
RUN a2enmod proxy proxy_http
RUN a2ensite 000-* 001-*
```

The script is now in our container. But it will do nothing untill we call it. What we are going to do is call it from our **apache2-foreground** script :

```bash
# Labo HTTP RES
echo "Setup for the RES lab"
echo "Static app URL  : $STATIC_APP"
echo "Dynamic app URL : $DYNAMIC_APP"

php /var/apache2/template/config-template.php > /etc/apache2/sites-available/001-reverse-proxy.conf```

## Build it and test it

to test it we have to run the following command in the terminal to start the server:

```bash
$ docker run -d -e STATIC_APP=172.17.0.2:80 -e DYNAMIC_APP=172.17.0.3:3000  -p 8080:80 res/apache_rp
```

## Bonus steps

### Load balancing : multiple server nodes

First, we have to enable the Apache modules for the load balancing. Edit the ``Dockerfile`` of the reverse proxy image like below :

```dockerfile
FROM php:7.0-apache

RUN apt-get update &&\
    apt-get install -y vim

# Adding our apache script in the bin directory
COPY apache2-foreground /usr/local/bin/

# Adding our templates to the apache2 directory of our container.
COPY templates /var/apache2/templates

COPY conf/ /etc/apache2

RUN a2enmod proxy proxy_http proxy_balancer lbmethod_byrequests
RUN a2ensite 000-* 001-*
```

We had the modules ``proxy_balancer`` and ``lbmethod_byrequests``


Now, we can write the new configuration file. According to the Apache's documentation we have to write some "Proxy balancer" tags to use its in the ``ProxyPass`` and the `ProxyPassReverse`. Below the configuration file :

```php
<?php
$dynamic_app = getenv('DYNAMIC_APP');
$static_app  = getenv('STATIC_APP');
$dynamic_app2 = getenv('DYNAMIC_APP2');
$static_app2  = getenv('STATIC_APP2');
?>

<VirtualHost *:80>
	ServerName demo.res.ch

	<Proxy "balancer://cities">
    		BalancerMember "http://<?php print $dynamic_app ?>"
    		BalancerMember "http://<?php print $dynamic_app2 ?>"
	</Proxy>

	ProxyPass '/api/cities/' 'balancer://cities/'
	ProxyPassReverse '/api/cities/' 'balancer://cities/'

	<Proxy "balancer://static">
    		BalancerMember "http://<?php print $static_app ?>"
    		BalancerMember "http://<?php print $static_app2 ?>"
	</Proxy>

	ProxyPass '/' 'balancer://static/'
	ProxyPassReverse '/' 'balancer://static/'
</VirtualHost>
```

#### To test it :

```bash
$ docker run -d res/apache_php
$ docker run -d res/apache_php
$ docker run -d res/express_cities
$ docker run -d res/express_cities

$ docker run -d -e STATIC_APP=172.17.0.2:80 -e STATIC_APP2=172.17.0.3:80 -e DYNAMIC_APP=172.17.0.4:3000 -e DYNAMIC_APP2=172.17.0.5:3000  -p 8080:80 apache_rp

```

Now that all containers are started, use your browser to go on ``demo.res.ch``  and check that it working correctly.
then kill one of the container and reload the page and see that it is still running

### Load balancing : round-robin vs sticky sessions

We just followed the steps of the apache documentation and add the ``header`` module in the dockerfile
```dockerfile
#Image Docker du serveur Apache
FROM php:7.2-apache

#Installation de vim
RUN apt-get update && \
apt-get install -y vim

#Copie le code source dans le container
COPY apache2-foreground /usr/local/bin/
COPY template /var/apache2/template
COPY conf/ /etc/apache2

#enable les sites
RUN a2enmod proxy proxy_http proxy_balancer lbmethod_byrequests headers
RUN a2ensite 000-* 001-*
```

```php
?php
$dynamic_app = getenv('DYNAMIC_APP');
$static_app  = getenv('STATIC_APP');
$dynamic_app2 = getenv('DYNAMIC_APP2');
$static_app2  = getenv('STATIC_APP2');
?>

<VirtualHost *:80>
	ServerName demo.res.ch

	Header add Set-Cookie "ROUTEID=.%{BALANCER_WORKER_ROUTE}e; path=/" env=BALANCER_ROUTE_CHANGED
	<Proxy "balancer://cities">
    		BalancerMember "http://<?php print $dynamic_app ?>" route=1
    		BalancerMember "http://<?php print $dynamic_app2 ?>" route=2
		ProxySet stickysession=ROUTEID
	</Proxy>

	ProxyPass '/api/cities/' 'balancer://cities/'
	ProxyPassReverse '/api/cities/' 'balancer://cities/'

	<Proxy "balancer://static">
    		BalancerMember "http://<?php print $static_app ?>"
    		BalancerMember "http://<?php print $static_app2 ?>"
	</Proxy>

	ProxyPass '/' 'balancer://static/'
	ProxyPassReverse '/' 'balancer://static/'
</VirtualHost>
```
